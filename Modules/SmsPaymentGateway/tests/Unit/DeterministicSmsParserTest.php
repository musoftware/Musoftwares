<?php

namespace Modules\SmsPaymentGateway\Tests\Unit;

use Tests\TestCase;
use Modules\SmsPaymentGateway\Services\DeterministicSmsParser;

class DeterministicSmsParserTest extends TestCase
{
    protected DeterministicSmsParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DeterministicSmsParser();
    }

    /**
     * @test
     */
    public function it_extracts_real_vodafone_cash_sms_sample_1()
    {
        // REAL PRODUCTION SMS from laravel.log
        $message = "تم استلام مبلغ 488 جنيه من رقم  01092270741 ؛ المسجل بإسم Nourhan A Ali رصيدك الحالي 831.49 تاريخ العملية 23:06 25-11-27 رقم العملية  015756930737.دلوقتي تقدر تسحب من محفظتك برسوم 5 جنيه بس بدل 1%! كلم *9*999# واشترك علشان تسحب لحد 5000 جنيه شهريًا من محفظتك برسوم ثابتة وأوفر! تابع كل مصروفاتك من تاريخ المعاملات على أبلكيشن أنا فودافون http://vf.eg/vfcash";
        $sender = "VF-Cash";

        $result = $this->parser->parse($message, $sender);

        $this->assertNotNull($result);
        $this->assertEquals(488.00, $result['amount']);
        $this->assertEquals(831.49, $result['balance']);
        $this->assertEquals('EGP', $result['currency']);
        $this->assertEquals('01092270741', $result['phone_number']);
        $this->assertEquals('015756930737', $result['reference_number']);
        $this->assertEquals('VF-Cash', $result['sender']);
        $this->assertFalse($result['is_instapay']);
    }

    /**
     * @test
     */
    public function it_extracts_real_vodafone_cash_sms_sample_2()
    {
        // REAL PRODUCTION SMS from laravel.log
        $message = "تم استلام مبلغ 3000 جنيه من رقم  01044306675 ؛ المسجل بإسم Ahmed A Mohamed رصيدك الحالي 3007.94 تاريخ العملية 19:39 25-12-01 رقم العملية  015847083619.دلوقتي تقدر تسحب من محفظتك برسوم 5 جنيه بس بدل 1%! كلم *9*999# واشترك علشان تسحب لحد 5000 جنيه شهريًا من محفظتك برسوم ثابتة وأوفر! تابع كل مصروفاتك من تاريخ المعاملات على أبلكيشن أنا فودافون http://vf.eg/vfcash";
        $sender = "VF-Cash";

        $result = $this->parser->parse($message, $sender);

        $this->assertNotNull($result);
        $this->assertEquals(3000.00, $result['amount']);
        $this->assertEquals(3007.94, $result['balance']);
        $this->assertEquals('01044306675', $result['phone_number']);
        $this->assertEquals('015847083619', $result['reference_number']);
    }

    /**
     * @test
     */
    public function it_extracts_real_vodafone_cash_sms_sample_3_with_decimal_amount()
    {
        // REAL PRODUCTION SMS from laravel.log
        $message = "تم استلام مبلغ 50.00 جنيه من رقم  01123477210 ؛ المسجل بإسم Yassin M Dawoud رصيدك الحالي 2616.01 تاريخ العملية 13:51 25-12-06 رقم العملية  015971428966.دلوقتي تقدر تسحب من محفظتك برسوم 5 جنيه بس بدل 1%! كلم *9*999# واشترك علشان تسحب لحد 5000 جنيه شهريًا من محفظتك برسوم ثابتة وأوفر! تابع كل مصروفاتك من تاريخ المعاملات على أبلكيشن أنا فودافون http://vf.eg/vfcash";
        $sender = "VF-Cash";

        $result = $this->parser->parse($message, $sender);

        $this->assertNotNull($result);
        $this->assertEquals(50.00, $result['amount']);
        $this->assertEquals(2616.01, $result['balance']);
        $this->assertEquals('01123477210', $result['phone_number']);
        $this->assertEquals('015971428966', $result['reference_number']);
    }

    /**
     * @test
     */
    public function it_rejects_promotional_cashback_messages()
    {
        // Constructed from blacklist regex in codebase rules
        $message = "مبروك كسبت 50 جنيه كاش باك في محفظتك لاستخدامك تطبيق فودافون كاش";
        $sender = "VF-Cash";

        $result = $this->parser->parse($message, $sender);

        $this->assertNull($result); // Must reject as promotional
    }

    /**
     * @test
     */
    public function it_normalizes_phone_numbers_correctly()
    {
        // Original 10 digit, no prefix
        $this->assertEquals('01092270741', $this->parser->normalizePhoneNumber('1092270741'));
        
        // 20 Country Code
        $this->assertEquals('01092270741', $this->parser->normalizePhoneNumber('201092270741'));
        
        // 00 Country Code Prefix
        $this->assertEquals('01092270741', $this->parser->normalizePhoneNumber('00201092270741'));
        
        // With spaces
        $this->assertEquals('01092270741', $this->parser->normalizePhoneNumber('010 922 70 741'));
    }

    /**
     * @test
     */
    public function it_extracts_real_instapay_arabic_sms()
    {
        $message = "يرجى العلم انه تم تنفيذ تحويل لحظي بمبلغ 5.00 جم إلى حسابك المنتهي بـ ********6286 من MAHMOUD AMIN MOHAMED AM برقم مرجعي 6b2d1e88 بتاريخ 30-04-2026 14:52 للمزيد، برجاء الاتصال بـ 19666";
        $sender = "InstaPay";

        $result = $this->parser->parse($message, $sender);

        $this->assertNotNull($result);
        $this->assertTrue($result['is_instapay']);
        $this->assertEquals(5.00, $result['amount']);
        $this->assertEquals('6b2d1e88', $result['reference_number']);
        $this->assertNull($result['balance']); 
    }

    /**
     * @test
     */
    public function it_extracts_real_instapay_english_sms()
    {
        $message = "Kindly note your account ********4601 was credited with IPN Inward with amount EGP 5750.00 on 30-04-2026 at 17:24 from HANAN HAMDY ABDELHAM with reference 2b86c95a For more information, please contact 19666.";
        $sender = "InstaPay";

        $result = $this->parser->parse($message, $sender);

        $this->assertNotNull($result);
        $this->assertTrue($result['is_instapay']);
        $this->assertEquals(5750.00, $result['amount']);
        $this->assertEquals('2b86c95a', $result['reference_number']);
        $this->assertNull($result['balance']); 
    }

    /**
     * @test
     */
    public function it_extracts_real_vodafone_cash_sms_from_ladyturbans()
    {
        $message = "تم استلام مبلغ 100 جنيه من رقم 01095961177 المسجل بإسم Ahmed E Ahmed على رقم محفظتك  01015218548.\nرصيدك الحالي: 152.38 جنيه\nتاريخ العملية: 17:10 26-04-25\nرقم العملية: 019450637070\nتابع كل مصروفاتك من تاريخ المعاملات على أبلكيشن أنا فودافون http://vf.eg/vfcash";
        $sender = "VF-Cash";

        $result = $this->parser->parse($message, $sender);

        $this->assertNotNull($result);
        $this->assertFalse($result['is_instapay']);
        $this->assertEquals(100.00, $result['amount']);
        $this->assertEquals('01095961177', $result['phone_number']); // already normalized
        $this->assertEquals(152.38, $result['balance']);
        $this->assertEquals('019450637070', $result['reference_number']);
    }

    /**
     * @test
     */
    public function it_extracts_real_english_wallet_sms()
    {
        $message = "Apr 30, 2026 4:44:09 PM: Received EGP10 from 00201095961177 to Mobile Account Number 2559. Ref: 019589803303 Available Balance: 2023.65";
        $sender = "VF-Cash";

        $result = $this->parser->parse($message, $sender);

        $this->assertNotNull($result);
        $this->assertFalse($result['is_instapay']);
        $this->assertEquals(10.00, $result['amount']);
        $this->assertEquals('01095961177', $result['phone_number']); // 00201095961177 normalized
        $this->assertEquals(2023.65, $result['balance']);
        $this->assertEquals('019589803303', $result['reference_number']);
    }
}
