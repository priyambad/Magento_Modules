<?php

namespace Redington\Company\Helper;

class CompanyResources {
    private $salesResources = [
        "Magento_Company::index" => "allow",
        "Magento_Sales::all" => "allow",
        "Magento_Sales::place_order" => "allow",
        "Magento_Sales::payment_account" => "allow",
        "Magento_Sales::view_orders" => "allow",
        "Magento_Sales::view_orders_sub" => "allow",
        "Magento_NegotiableQuote::all" => "allow",
        "Magento_NegotiableQuote::view_quotes" => "allow",
        "Magento_NegotiableQuote::manage" => "allow",
        "Magento_NegotiableQuote::checkout" => "allow",
        "Magento_NegotiableQuote::view_quotes_sub" > "allow",
        "Magento_Company::view" => "allow",
        "Magento_Company::view_account" => "allow",
        "Magento_Company::edit_account" => "deny",
        "Magento_Company::view_address" => "allow",
        "Magento_Company::edit_address" => "deny",
        "Magento_Company::contacts" => "allow",
        "Magento_Company::payment_information" =>"allow",
        "Magento_Company::user_management" => "allow",
        "Magento_Company::roles_view" => "deny",
        "Magento_Company::roles_edit" => "deny",
        "Magento_Company::users_view" => "allow",
        "Magento_Company::users_edit" =>"deny",
        "Magento_Company::credit" => "deny",
        "Magento_Company::credit_history" => "deny"
    ];
    private $financeResource = [
        "Magento_Company::index" => "allow",
        "Magento_Sales::all" => "deny",
        "Magento_Sales::place_order" => "deny",
        "Magento_Sales::payment_account" => "deny",
        "Magento_Sales::view_orders" => "allow",
        "Magento_Sales::view_orders_sub" => "allow",
        "Magento_NegotiableQuote::all" => "deny",
        "Magento_NegotiableQuote::view_quotes" => "deny",
        "Magento_NegotiableQuote::manage" => "deny",
        "Magento_NegotiableQuote::checkout" => "deny",
        "Magento_NegotiableQuote::view_quotes_sub" > "deny",
        "Magento_Company::view" => "allow",
        "Magento_Company::view_account" => "allow",
        "Magento_Company::edit_account" => "deny",
        "Magento_Company::view_address" => "allow",
        "Magento_Company::edit_address" => "deny",
        "Magento_Company::contacts" => "allow",
        "Magento_Company::payment_information" =>"allow",
        "Magento_Company::user_management" => "allow",
        "Magento_Company::roles_view" => "deny",
        "Magento_Company::roles_edit" => "deny",
        "Magento_Company::users_view" => "allow",
        "Magento_Company::users_edit" =>"deny",
        "Magento_Company::credit" => "allow",
        "Magento_Company::credit_history" => "allow"
    ];

    public function getSalesResources(){
        return $this->salesResources;
    }
    public function getFinanceResource(){
        return $this->financeResource;
    }
}