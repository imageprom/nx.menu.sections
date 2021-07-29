<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
include_once('core_funtions.php');

if(!isset($arParams['CACHE_TIME']))
	$arParams['CACHE_TIME'] = 36000000;

$arParams['ID'] = intval($arParams['ID']);
$arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID']);

$arParams['DEPTH_LEVEL'] = intval($arParams['DEPTH_LEVEL']);
if($arParams['DEPTH_LEVEL'] <= 0)
	$arParams['DEPTH_LEVEL'] = 1;

$arParams['SORT_FIELD'] = 'NAME';
	
if($this->StartResultCache()) {

	if(!CModule::IncludeModule('iblock')) { $this->AbortResultCache();}
	else {
	   
	    $arResult['SECTIONS_PARENT'][0]='';
		$arFilter = array(
			'IBLOCK_ID'=>$arParams['IBLOCK_ID'],
			'GLOBAL_ACTIVE'=>'Y',
			'IBLOCK_ACTIVE'=>'Y',
			'<='.'DEPTH_LEVEL' => $arParams['DEPTH_LEVEL'],
		);
		$arOrder = array(
			'left_margin' => 'asc',
			$arParams['SORT_FIELD'] => 'asc',
		);

		$rsSections = CIBlockSection::GetList($arOrder, $arFilter, false, array(
			'ID',
			'DEPTH_LEVEL',
			'IBLOCK_SECTION_ID',
			'NAME',
			'PICTURE',
			'SECTION_PAGE_URL',
			'SORT', 
			'LEFT_MARGIN',
		));
		
		if($arParams['IS_SEF'] !== 'Y')
			$rsSections->SetUrlTemplates('', $arParams['SECTION_URL']);
		else
			$rsSections->SetUrlTemplates('', $arParams['SEF_BASE_URL'].$arParams['SECTION_PAGE_URL']);
		

		while($arSection = $rsSections->GetNext()) {
			    $arResult['SECTIONS'][$arSection['ID']]=array(
				'ID' => $arSection['ID'],
				'DEPTH_LEVEL' => $arSection['DEPTH_LEVEL'],
				'~NAME' => $arSection['~NAME'],
				'NAME' => $arSection['NAME'],
				'URL' => $arSection['~SECTION_PAGE_URL'],
				'PICTURE' => $arSection['PICTURE'],
				'IBLOCK_SECTION_ID' => intval($arSection['IBLOCK_SECTION_ID']),
				'SORT' => $arSection['SORT'],
				'SORT' => $arSection['SORT'],
				'LEFT_MARGIN' => $arSection['LEFT_MARGIN'],
				'IS_PARENT'=> '',
				'IS_ELEMENT' => false
				);
				
				if ($arSection['DEPTH_LEVEL']<$arParams['DEPTH_LEVEL'] ) { 
					$arResult['SECTIONS_PARENT'][] = $arSection['ID'];
				}
				else $arResult['SECTIONS'][$arSection['ID']]['IS_PARENT'] = '';
			$arResult['ELEMENT_LINKS'][$arSection['ID']] = array();
		}
		
	$arSelect = array('ID', 'IBLOCK_ID', 'DETAIL_PAGE_URL', 'IBLOCK_SECTION_ID', 'NAME', 'SORT');
	$arFilter = array(
		'ACTIVE' => 'Y',
		'IBLOCK_ID' => $arParams['IBLOCK_ID'],
		'SECTION_ID'=> $arResult['SECTIONS_PARENT']
	);
	$arSort = array($arParams['SORT_FIELD'] => 'asc');
	$rsElements = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
	$arResult['ELEMENTS'] = array();
	
	if(($arParams['IS_SEF'] === 'Y') && (strlen($arParams['DETAIL_PAGE_URL']) > 0))
		$rsElements->SetUrlTemplates($arParams['SEF_BASE_URL'].$arParams['DETAIL_PAGE_URL']);
	
	while($arElement = $rsElements->GetNext()) {   
	    
		$arResult['ELEMENTS'][$arElement['ID']] = array(
			'ID' => $arElement['ID'],
			'DEPTH_LEVEL' => ($arResult['SECTIONS'][$arElement['IBLOCK_SECTION_ID']]['DEPTH_LEVEL']+1),
			'~NAME' => $arElement['~NAME'],
			'NAME' => $arElement['NAME'],
			'URL' => $arElement['~DETAIL_PAGE_URL'],
			'IBLOCK_SECTION_ID' => intval($arElement['IBLOCK_SECTION_ID']),
			'SORT' => $arElement['SORT'],
			'IS_PARENT'=> '',
			'LEFT_MARGIN' => false,
			'IS_ELEMENT' => true,
		);
		
		if ($arResult['ELEMENTS'][$arElement['ID']]['DEPTH_LEVEL'] == 1) {
			$arResult['ELEMENTS'][$arElement['ID']]['URL'] = $arParams['SEF_BASE_URL'].$arResult['ELEMENTS'][$arElement['ID']]['URL'];
		}
		
		$arResult['ITEMS'][intval($arElement['IBLOCK_SECTION_ID'])][] = $arResult['ELEMENTS'][$arElement['ID']];
	}
		
		$this->EndResultCache();
	}
}

$count_elem = 0;

$max = $arResult['SECTIONS'][count($arResult['SECTIONS'])-1]['LEFT_MARGIN'];
$cnt = 0;

while (count($arResult['ELEMENTS']) > 0 ) {
	$cnt++;
	$current = array_shift($arResult['ELEMENTS']);
	$local_max = 0;
	
	foreach($arResult['SECTIONS'] as $item) {
		if($current['IBLOCK_SECTION_ID'] == $item['ID'] && !$item['IS_ELEMENT']) {
			if($local_max <  $item['LEFT_MARGIN']) $local_max =  $item['LEFT_MARGIN'];
			$current['LEFT_MARGIN'] = $item['LEFT_MARGIN'] + 1;
			array_walk($arResult['SECTIONS'], 'left_margin_add', $current['LEFT_MARGIN']);
		}
		if( $item['LEFT_MARGIN'] > $max ) $max = $item['LEFT_MARGIN'] + 1;
	}
	
	if(!$current['LEFT_MARGIN'] && $local_max > 0 ) { $current['LEFT_MARGIN'] = $local_max + 1; array_walk($arResult['SECTIONS'], 'left_margin_add', $current['LEFT_MARGIN']);}
	elseif(!$current['LEFT_MARGIN']) $current['LEFT_MARGIN'] = $max + 1;
	
	$arResult['SECTIONS'][] = $current;
	unset($arResult['ELEMENTS'][$current['ID']]);
	
}

uasort($arResult['SECTIONS'], 'menu_margin_sort'); 
$result = array();


$level = 1;
$cnt = 0;

foreach($arResult['SECTIONS'] as $current) {

	$res = array();  
	$res[0] = $current['~NAME'];  
	$res[1] = $current['URL']; 
	$res[2] = array($current['URL']); 
	$res[3] = array(
					'FROM_IBLOCK'=>1, 
					'IS_PARENT' => $current['IS_PARENT'], 
					'DEPTH_LEVEL' => $current['DEPTH_LEVEL'], 
					'PICTURE' => CFile::GetPath($current['PICTURE'])
				);
	//$res[4]=$current['IBLOCK_SECTION_ID'];
	$result[] = $res;
	if($current['DEPTH_LEVEL'] > $level && !$current['IS_ELEMENTIS_ELEMENT']) {
		$result[$cnt-1][3]['IS_PARENT'] = 1;
	}
	
	$level  = $current['DEPTH_LEVEL'];
	$cnt++;
}
return $result;
?>