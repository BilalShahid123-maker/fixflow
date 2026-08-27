<?php

namespace Database\Seeders;

use App\Models\EvalCase;
use Illuminate\Database\Seeder;

class EvalDatasetSeeder extends Seeder
{
    public function run(): void
    {
        $cases = [
            ['Water leaking under kitchen sink', 'There is water pooling under the sink and the cabinet is soaked. It will not stop dripping.', 'plumbing', 'high', false],
            ['Smoke coming from electrical outlet', 'Black smoke and a burning smell are coming from the outlet next to the TV. It sparked when I plugged in my laptop.', 'electrical', 'critical', true],
            ['AC stopped cooling', 'The air conditioner runs but blows warm air only. Set to 68 but the unit reads 82.', 'hvac', 'medium', false],
            ['Garbage disposal not working', 'The disposal hums but the blades do not turn. It might be jammed.', 'appliance', 'low', false],
            ['Water heater pilot light out', 'The pilot went out and I cannot relight it. No hot water for two days.', 'plumbing', 'medium', false],
            ['Crack in the ceiling drywall', 'There is a visible crack running across the living room ceiling.', 'structural', 'low', false],
            ['Flickering lights throughout unit', 'The lights flicker constantly and one outlet feels warm to the touch.', 'electrical', 'high', false],
            ['Sewage smell in bathroom', 'There is a strong sewage odor coming from the bathroom drain all day.', 'plumbing', 'medium', false],
            ['Fire alarm chirping with smoke', 'The smoke alarm is chirping and there is light smoke near the kitchen stove.', 'electrical', 'critical', true],
            ['Thermostat unresponsive', 'The thermostat screen is blank and will not respond to any buttons.', 'hvac', 'medium', false],
            ['Door hinge squeaking', 'The front door hinge squeaks loudly when opening and closing.', 'general', 'low', false],
            ['Radiator leaking steam', 'Steam is hissing and leaking from the radiator valve in the bedroom.', 'hvac', 'high', false],
            ['Window seal broken', 'There is moisture trapped between the glass panes of the bedroom window.', 'structural', 'low', false],
            ['Electrical panel sparking', 'The main breaker panel sparks when I touch the switches. Smells hot.', 'electrical', 'critical', true],
            ['Fridge leaking water onto floor', 'Water is pooling under the refrigerator and soaking the kitchen floor.', 'appliance', 'medium', false],
            ['Bathroom sink drain clogged', 'The bathroom sink drains very slowly and backs up after a few seconds.', 'plumbing', 'low', false],
            ['Furnace making grinding noise', 'The furnace makes a loud grinding sound when it starts up in the morning.', 'hvac', 'high', false],
            ['Basement flooding from pipe burst', 'A pipe burst and water is rapidly flooding the basement utility room.', 'plumbing', 'critical', true],
            ['Dim kitchen under-cabinet lights', 'The lights under the kitchen cabinets are very dim and flicker.', 'electrical', 'low', false],
            ['Stove burner not igniting', 'One gas burner will not ignite. I smell a faint gas odor near it.', 'appliance', 'medium', false],
        ];

        foreach ($cases as [$title, $description, $category, $severity, $emergency]) {
            EvalCase::create([
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'severity' => $severity,
                'emergency' => $emergency,
                'split' => $this->assignSplit(),
                'source_note' => 'curated demo set',
            ]);
        }

        $this->command?->info('Seeded '.count($cases).' labeled evaluation cases.');
    }

    private int $cursor = 0;

    private function assignSplit(): string
    {
        $splits = ['eval', 'eval', 'eval', 'eval', 'holdout'];

        $this->cursor++;

        return $splits[($this->cursor - 1) % count($splits)];
    }
}
