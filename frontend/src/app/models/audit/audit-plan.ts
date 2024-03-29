﻿export interface AuditPlan {
    id: number;
    data?: Array<any>;
    company_name?:string;
    zipcode?:string;
    address?:string;
    country_name?:string;
    state_name?:string;
    city?:string;
    units?:any;
    manday?: any;
    arrEnumStatus?:any;
    status?:any;
    arrUnitEnumStatus?:any;
    auditreviews?:any;
    application_lead_auditor?:any;
    inspectionplan?:any;
    history?:any;
    app_id?:any;
    invoice_id?:any;
    offer_id?:any;
    quotation_manday?:any;
    actual_manday?:any;
    created_by?:any;
    created_at?:any;
    status_name?:any;
    application_lead_auditor_name?:any;
    created_by_name?:any;
    certificate_created_at_date?:any;
    audit_id?:any;
    plan_status?:any;
    reviewer_id?:any;
    arrEnumPlanStatus?:any;
    showCertificateGenerate?:any;
    plan_status_name?:any;
    showSubmitRemediationForAuditor?:any;
    showSendBackRemediationToCustomer?:any;
    showSubmitRemediationForReviewer?:any;
    showSendBackRemediationToAuditor?:any;
    certificate_status?:any;
    arrEnumCertificateStatus?:any;
    certFileList?:any;
    apiUrl?:any;
    certificate_generated_date?:any;
    certificate_valid_until?:any;
    creator?:any;
    checklistreviews?:any;
	certificate_id?:any;
	product_addition_id?:any;
    version?:any;
    certificate_reviewer_status?:any;
    certificate_review_status?:any;
    certificate_reviewer_id?:any;
    standard_label?:any;
    certificate_created_at?:any;
    overdue_status?:any;
    standard_name?:any;
    standard_id?:any;
    extension_by?:any;
    extension_date?:any;

    certificate_status_name?:any;
    reviews?:any;
    cb_reason?:any;
    cb_file?:any;
    cb_data?:any;
    reviewer_details?:any;
    cb_date?:any;
    reviewer_canassign:any;
    showSubmitFollowupAudit?:any;

    showSendBackFollowupRemediationToLeadAuditor?:any;
    showSendBackFollowupRemediationToUnitAuditor?:any;
    followup_history:any;
    followup_status?:any;
    followupinspectionplan?:any;
    inspectionplan_id?:any;
    followupinspectionplan_id?:any;
    followupauditreviews?:any;
    showSubmitFollowupRemediationForReviewer?:any;
    followup_application_lead_auditor_name?:any;
    followup_actual_manday?:any;
    followup_created_by_name?:any;
    followup_created_at?:any;
    followup_application_lead_auditor?:any;

    customer_review_created_by_name?:any;
    customer_review_created_at?:any;
    customer_review_comment?:any;
    followup_customer_review_created_by_name?:any;
    followup_customer_review_created_at?:any;
    followup_customer_review_comment?:any;

    share_plan_to_customer?:any;
    unannounced_audit_reason?:any;
    share_plan_to_customer_label?:any;
    audit_type?:any;
    showsendtocustomer?:any;
    showInspectionApproval?:any;
    show_followup_status?:any;
    canChangeMaterialComp?:any;
    
    inspection_created_by?:any;
    inspection_created_at?:any;
    inspection_sent_by?:any;
    inspection_sent_at?:any;

    followupinspection_created_by?:any;
    followupinspection_created_at?:any;
    followupinspection_sent_by?:any;
    followupinspection_sent_at?:any;

    auditoroverduecmt?:any;
    revieweroverduecmt?:any;

    type_label?:any;
    show_certificate_selection?:any;
    audit_type_name?:any;
    sel_brand_ch?:number;
    
}