<?php

return [
    // Cổng thanh toán MoMo — mặc định là môi trường TEST (sandbox), không trừ tiền thật
    'momo' => [
        'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'partner_code' => env('MOMO_PARTNER_CODE', 'MOMO'),
        'access_key' => env('MOMO_ACCESS_KEY', 'F8BBA842ECF85'),
        'secret_key' => env('MOMO_SECRET_KEY', 'K951B6PE1waDMi640xX08PD3vg6EkVlz'),
        // payWithMethod: trang MoMo cho chọn ví / thẻ ATM / thẻ tín dụng
        'request_type' => env('MOMO_REQUEST_TYPE', 'payWithMethod'),
    ],

    // Mã QR chuyển khoản ngân hàng theo chuẩn VietQR (quét bằng app ngân hàng bất kỳ)
    'vietqr' => [
        'bank' => env('VIETQR_BANK', 'MB'),           // mã ngân hàng: MB, VCB, TCB, ACB, BIDV, VPB...
        'account' => env('VIETQR_ACCOUNT', ''),        // số tài khoản nhận tiền
        'name' => env('VIETQR_NAME', 'BEP NHA SHOP'),  // tên chủ tài khoản (không dấu)
    ],
];
