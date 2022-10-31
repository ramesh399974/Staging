<?php

$basePath = realpath(Yii::$app->basePath);

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
	'reCaptchaSiteKey' => '6LfBCL4ZAAAAAHP4iNHhfde9ULSCDfwb8hnhr0Q-', // GCL Live
	'reCaptchaSecretKey' => '6LfBCL4ZAAAAAPVx58bJm_oBOSOcM2z0kW0IuNNK', // GCL Live
	'reCaptchaVerifyURL' => 'https://www.google.com/recaptcha/api/siteverify',
	'company_files' => $basePath . '/company_files/',
    'certification_standard_files' => $basePath . '/certified_standard_files/',
    'user_files' => $basePath . '/user_files/',
    'user_qualification_review_files' => $basePath . '/user_files/',
    'customer_approval_files' => $basePath . '/user_files/',
    'remediation_evidence_files' => $basePath . '/user_files/',
    'audit_files'=> $basePath . '/user_files/',
    'template_files'=> $basePath . '/template_files/',
    'site_path' => "http://localhost:4200/",
    'user_type' => ['user'=>1,'customer'=>2,'franchise'=>3],
    'certificate_files' => $basePath . '/certificate_files/',
    'application_checklist_file' => $basePath . '/application_checklist/',
    'image_files' => $basePath . '/images/',
    'library_files'=> $basePath . '/library_files/',
    'tc_files'=> $basePath . '/tc_files/',
	'signature_files'=> $basePath . '/signature_files/',
    'temp_files'=> $basePath . '/temp/',
    'certifiedbyothercb_file' => $basePath . '/certifiedbyothercb_file/',
	'customer_clientlogo_checklist_file'=> $basePath . '/customer_clientlogo_checklist_file/',
	'invoice_files'=> $basePath . '/invoice_files/',
	'report_files'=> $basePath . '/report_files/',
	'EncryptDecryptKey'=> '20GCL191nTlLtDcM',
	'qrcode_scan_url_for_draft' => "https://gcl-intl.com",	
	'certificate_file_download_path' => "http://localhost:4200/backend/web/site/",
];
