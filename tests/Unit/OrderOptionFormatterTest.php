<?php

namespace Tests\Unit;

use App\Support\OrderOptionFormatter;
use Tests\TestCase;

class OrderOptionFormatterTest extends TestCase
{
    public function test_associative_special_finish_details_keep_the_finish_code_and_side(): void
    {
        $this->assertSame(
            '特殊工艺: Laser, 特殊工艺单双面: Laser: 双面',
            OrderOptionFormatter::options([
                'special_finish' => ['laser'],
                'special_finish_on_sides' => ['laser' => 'both_sides'],
            ]),
        );
    }
}
