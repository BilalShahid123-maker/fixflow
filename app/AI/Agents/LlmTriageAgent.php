<?php

namespace App\AI\Agents;

use App\AI\Contracts\TriageAgent;
use App\AI\Dto\TriageResult;
use App\AI\RAG\EmbeddingService;
use App\AI\RAG\VectorSearch;
use App\AI\Safety\PromptSafety;
use App\Enums\IssueCategory;
use App\Enums\Severity;
use App\Models\KnowledgeChunk;
use App\Models\MaintenanceRequest;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use RuntimeException;

class LlmTriageAgent implements TriageAgent
{
    private const SYSTEM_PROMPT = <<<'TXT'
        You are the triage engine for a property management company.
        Classify tenant maintenance requests using ONLY the information in the message.
        Rules:
        - category must be one of the provided enum values; pick the closest fit.
        - severity: "critical" only when there is an immediate safety hazard (fire, smoke,
          sparking, gas smell, flooding); "high" for active leaks or loss of essential
          utilities; "medium" for malfunctioning but contained issues; "low" for cosmetic ones.
        - is_emergency mirrors severity "critical".
        - confidence reflects how certain the classification is given the wording:
          explicit descriptions 0.85-0.97, ambiguous 0.5-0.7. Never output above 0.97.
        - reasoning: one sentence citing the words that drove the decision. Never invent facts.
        TXT;

    public function __construct(
        private ?EmbeddingService $embeddingService = null,
        private ?VectorSearch $vectorSearch = null,
    ) {}

    public function triage(MaintenanceRequest $request): TriageResult
    {
        $rag = $this->retrieveKnowledgeContext($request);

        $response = Prism::structured()
            ->using(
                (string) config('fixflow.llm.provider'),
                (string) config('fixflow.llm.model'),
            )
            ->withSystemPrompt(self::SYSTEM_PROMPT)
            ->withSchema($this->schema())
            ->withPrompt($this->buildPrompt($request, $rag))
            ->withMaxTokens(500)
            ->asStructured();

        $data = $response->structured;

        if ($data === null) {
            throw new RuntimeException('LLM returned no structured output.');
        }

        return new TriageResult(
            category: $this->mapCategory($data['category'] ?? null),
            severity: $this->mapSeverity($data['severity'] ?? null),
            confidence: $this->clampConfidence($data['confidence'] ?? null),
            isEmergency: (bool) ($data['is_emergency'] ?? false),
            reasoning: trim((string) ($data['reasoning'] ?? '')),
            meta: [
                'provider' => (string) config('fixflow.llm.provider'),
                'model' => $response->meta->model,
                'request_id' => $response->meta->id,
                'input_tokens' => $response->usage->promptTokens,
                'output_tokens' => $response->usage->completionTokens,
                'finish_reason' => $response->finishReason->value,
                'rag_citations' => $rag['citations'] ?? [],
            ],
        );
    }

    private function schema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'triage_result',
            description: 'Classification of a tenant maintenance request.',
            properties: [
                new EnumSchema('category', 'Trade category of the issue.', array_column(IssueCategory::cases(), 'value')),
                new EnumSchema('severity', 'Urgency level of the issue.', array_column(Severity::cases(), 'value')),
                new NumberSchema('confidence', 'Certainty between 0 and 1.'),
                new BooleanSchema('is_emergency', 'True when immediate safety hazard.'),
                new StringSchema('reasoning', 'One-sentence justification.'),
            ],
            requiredFields: ['category', 'severity', 'confidence', 'is_emergency', 'reasoning'],
        );
    }

    private function retrieveKnowledgeContext(MaintenanceRequest $request): array
    {
        if ($this->embeddingService === null || $this->vectorSearch === null) {
            return ['context' => '', 'citations' => []];
        }

        $query = trim($request->title.' '.$request->description);
        $queryEmbedding = $this->embeddingService->embed([$query])[0] ?? null;

        if ($queryEmbedding === null || $queryEmbedding === []) {
            return ['context' => '', 'citations' => []];
        }

        $chunks = KnowledgeChunk::query()
            ->whereNotNull('embedding')
            ->with('document')
            ->get()
            ->map(fn (KnowledgeChunk $chunk) => [
                'chunk_id' => $chunk->getKey(),
                'document_id' => $chunk->knowledge_document_id,
                'title' => $chunk->document->title ?? '',
                'section' => $chunk->metadata['section'] ?? null,
                'content' => $chunk->content,
                'embedding' => $chunk->embedding,
            ])
            ->all();

        $topK = (int) config('fixflow.rag.search.top_k', 3);
        $minScore = (float) config('fixflow.rag.search.min_score', 0.0);

        $results = $this->vectorSearch->search($queryEmbedding, $chunks, $topK, $minScore);

        if ($results === []) {
            return ['context' => '', 'citations' => []];
        }

        $context = "\n\nRelevant maintenance knowledge:\n";
        $citations = [];

        foreach ($results as $i => $result) {
            $label = $i + 1;
            $section = $result['section'] ? " — {$result['section']}" : '';
            $context .= "[{$label}] {$result['title']}{$section}\n{$result['content']}\n\n";

            $citations[] = [
                'document_id' => $result['document_id'],
                'chunk_id' => $result['chunk_id'],
                'title' => $result['title'],
                'section' => $result['section'],
                'score' => $result['score'],
            ];
        }

        return ['context' => $context, 'citations' => $citations];
    }

    private function buildPrompt(MaintenanceRequest $request, array $rag): string
    {
        $untrusted = sprintf(
            "Title: %s\nDescription: %s",
            PromptSafety::sanitizeForStorage($request->title),
            PromptSafety::sanitizeForStorage($request->description),
        );

        $base = sprintf(
            "Unit: %s\nProperty: %s\n%s",
            $request->unit?->label ?? 'unknown',
            $request->unit?->property?->name ?? 'unknown',
            PromptSafety::encloseUntrusted($untrusted),
        );

        if ($rag['context'] !== '') {
            $base .= $rag['context'];
        }

        return $base;
    }

    private function mapCategory(mixed $value): IssueCategory
    {
        $category = IssueCategory::tryFrom((string) $value);

        if ($category === null) {
            throw new RuntimeException("LLM returned invalid category [{$value}].");
        }

        return $category;
    }

    private function mapSeverity(mixed $value): Severity
    {
        $severity = Severity::tryFrom((string) $value);

        if ($severity === null) {
            throw new RuntimeException("LLM returned invalid severity [{$value}].");
        }

        return $severity;
    }

    private function clampConfidence(mixed $value): float
    {
        if (! is_numeric($value)) {
            throw new RuntimeException('LLM returned non-numeric confidence.');
        }

        return min(0.97, max(0.0, (float) $value));
    }
}
