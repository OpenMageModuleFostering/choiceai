<?php
include("Client.php");
$facetlist=array();
$facetValue=array("broad bangles");


$facetValue=array("April");
$facetlist["gemstone_month_fq"]=$facetValue;


$facets=array();
$facets[]="frame_shape_fq";
$facets[]="brand_name_fq";
$facets[]="primary_frame_color_fq";
$facets[]="price_fq";
$facets[]="frame_size_fq";



$rangelist=array();
$rangeValue=array(array("from"=>"0", "to" => "500000"),array("from"=>"50", "to" => "100"));
//$facetlist["price_fq"]=$rangeValue;

$sortlist=array();
$sortlist['name']=1;

$options = array();
$options["stats"] = "price";

$_config = array(
		'sitename'=>'cl-sandbox-1375791452266',
		'apikey'=>'ad93787f2f479e3e63b0161b3877ec7a',
	);


$choiceai= new ChoiceAI_Client($_config);

$results =  $choiceai->setCond()
	    ->setRuleset("search")
	    ->setOtherOptions(array('wt'=>'json'))
	    ->setFilters($facetlist)
	    ->setDebug(true)
	    ->setQuery("diamond")
	    ->search();

echo '<br/>'.$results->getTotalHits().'<br/>';
foreach($results as $result){	
	echo $result->__get('name')."<br/>";
} 



?>
