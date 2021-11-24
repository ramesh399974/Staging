export interface Relation {

    id: number;
    name: string;
    type_name:string;
    type_name_id:number;
    wastage:string;
	code: string;
    description: string;
    status?: number;

    rel_declaration_company?:string;
    rel_declaration_contract?:any;
    rel_declaration_contract_name?:any;
    rel_declaration_interest?:string;
    rel_declaration_start_year?:any;
    rel_declaration_end_year?:any;

    rel_declaration_ft_company?:string;
    rel_declaration_ft_contract?:any;
    rel_declaration_ft_contract_name?:any;
    rel_declaration_ft_interest?:string;
    rel_declaration_ft_start_year?:any;
    rel_declaration_ft_end_year?:any;

    self_declaration_2ndQ_company?:string;
    self_declaration_2ndQ_contract?:any;
    self_declaration_2ndQ_contract_name?:any;
    self_declaration_2ndQ_interest?:string;
    self_declaration_2ndQ_start_year?:any;
    self_declaration_2ndQ_end_year?:any;

    self_declaration_3rdQ_company?:string;
    self_declaration_3rdQ_contract?:any;
    self_declaration_3rdQ_contract_name?:any;
    self_declaration_3rdQ_interest?:string;
    self_declaration_3rdQ_start_year?:any;
    self_declaration_3rdQ_end_year?:any;

    self_declaration_4thQ_company?:string;
    self_declaration_4thQ_contract?:any;
    self_declaration_4thQ_contract_name?:any;
    self_declaration_4thQ_interest?:string;
    self_declaration_4thQ_start_year?:any;
    self_declaration_4thQ_end_year?:any;

    self_declaration_5thQ_company?:string;
    self_declaration_5thQ_contract?:any;
    self_declaration_5thQ_contract_name?:any;
    self_declaration_5thQ_interest?:string;
    self_declaration_5thQ_start_year?:any;
    self_declaration_5thQ_end_year?:any;

    self_declaration_6thQ_company?:string;
    self_declaration_6thQ_contract?:any;
    self_declaration_6thQ_contract_name?:any;
    self_declaration_6thQ_interest?:string;
    self_declaration_6thQ_start_year?:any;
    self_declaration_6thQ_end_year?:any;


    close_relation_1st_name?:string;
    close_relation_1st_declaration_relation?:string;
    close_relation_1st_declaration_relation_name ?:any;
    close_relation_1st_declaration_company?:string;
    close_relation_1st_declaration_contract?:any;
    close_relation_1st_declaration_contract_name?:any;
    close_relation_1st_declaration_interest?:string;
    close_relation_1st_declaration_start_year?:any;
    close_relation_1st_declaration_end_year?:any;

    close_relation_2nd_name?:string;
    close_relation_2nd_declaration_relation?:string;
    close_relation_2nd_declaration_relation_name ?:any;
    close_relation_2nd_declaration_company?:string;
    close_relation_2nd_declaration_contract?:any;
    close_relation_2nd_declaration_contract_name?:any;
    close_relation_2nd_declaration_interest?:string;
    close_relation_2nd_declaration_start_year?:any;
    close_relation_2nd_declaration_end_year?:any;



    close_relation_3rd_name?:string;
    close_relation_3rd_declaration_relation?:string;
    close_relation_3rd_declaration_relation_name ?:any;
    close_relation_3rd_declaration_company?:string;
    close_relation_3rd_declaration_contract?:any;
    close_relation_3rd_declaration_contract_name?:any;
    close_relation_3rd_declaration_interest?:string;
    close_relation_3rd_declaration_start_year?:any;
    close_relation_3rd_declaration_end_year?:any;

}