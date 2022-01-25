<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-09-20 09:40:38 CST
 *  Description:     ArrTest.php's function description
 *  Version:         1.0.0.20180920-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-09-20 09:40:38 CST initialized the file
 ******************************************************************************/

use PHPUnit\Framework\TestCase;

final class ArrTest extends TestCase
{

    protected $m_arrTestArr = [];


    public function setUp()
    {
        $this->m_arrTestArr = [
            'A' => [
                'B' => 'C',
                'D' => 'E',
                'F' => [
                    'G',
                    'H',
                ],
                'I' => 'J',
                1   => '111',
                2   => '222'
            ],
            'K' => 'L',
            'M' => [
                'N' => 'O'
            ],
            1 => [
                'NAME' => 'N1',
                'AGE'  => '12',
                'META' => [
                    'FROM' => 'CN',
                ]
            ],
            2 => [
                'NAME' => 'N2',
                'AGE'  => '15',
                'META' => [
                    'FROM'   => 'US',
                    'BEFORE' => 'YESTERDAY'
                ]
            ]
        ];

    }


    public function testGet()
    {
        $sVal = \Capsheaf\Utils\Types\Arr::get($this->m_arrTestArr, '1.META.FROM');

        $this->assertEquals('CN', $sVal);
    }


    public function testOnlyFields()
    {
        $arrSource = $this->m_arrTestArr;

        \Capsheaf\Utils\Types\Arr::onlyFields(
            $arrSource, [
                '*.META.BEFORE'
            ]
        );

        $this->assertEquals(
            [
                2=> [
                    'META' => [
                        'BEFORE' => 'YESTERDAY'
                    ],
                ],
            ], $arrSource
        );
    }


    public function testExceptFields()
    {
        $arrSource = $this->m_arrTestArr;

        \Capsheaf\Utils\Types\Arr::exceptFields(
            $arrSource, [
                '*.META.BEFORE'
            ]
        );

        $this->assertEquals(
            [
                'A' => [
                    'B' => 'C',
                    'D' => 'E',
                    'F' => [
                        'G',
                        'H',
                    ],
                    'I' => 'J',
                    1   => '111',
                    2   => '222'
                ],
                'K' => 'L',
                'M' => [
                    'N' => 'O'
                ],
                1 => [
                    'NAME' => 'N1',
                    'AGE'  => '12',
                    'META' => [
                        'FROM' => 'CN',
                    ],
                ],
                2 => [
                    'NAME' => 'N2',
                    'AGE'  => '15',
                    'META' => [
                        'FROM' => 'US'
                    ],
                ]
            ], $arrSource
        );
    }

}
