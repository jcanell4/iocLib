<?php
if (!defined('DOKU_INC')) define('DOKU_INC', realpath('../../../') . '/');
if(!defined('DOKU_CONF')) define('DOKU_CONF',DOKU_INC.'conf/');

//require_once DOKU_INC.'inc/preload.php';

require_once DOKU_INC.'inc/inc_ioc/ioc_load.php';
require_once DOKU_INC.'inc/inc_ioc/ioc_project_load.php';

require_once DOKU_INC.'inc/init.php';




//$t = 'Text normal al començament <WIOCCL:IF condition="{##semestre##}==2">(primer if la condició es certa) segona opció parsejada: {##itinerariRecomanatS2##} lalala <WIOCCL:IF condition="{##semestre##}==2">(if niuat la condició es certa) segona opció parsejada: {##itinerariRecomanatS3##}</WIOCCL:IF>lelele </WIOCCL:IF> (això està fora dels if) asdfasd fasd un altre de diferent: <WIOCCL:IF condition="{##semestre##}==3">(això es un altre if la condició es falsa) segona opció parsejada: {##itinerariRecomanatS2##}</WIOCCL:IF> (això es el final sense ifs) dddd';

//$t = '::table:TA0
//  :title:Planificació UFX
//  :type:io_pt
//  :footer::
//^  tipus	^  eina	 ^  opcionalitat	 ^  puntuable  ^
//<WIOCCL:FOREACH var="item" array="{##einesAprenentatge##}">| {##item[\'tipus\']##} | {##item[\'eina\']##} | {##item[\'opcionalitat\']##} | <WIOCCL:IF condition="{##item[\'puntuable\']##}==\'true\'">si</WIOCCL:IF><WIOCCL:IF condition="{##item[\'puntuable\']##}==\'false\'">no</WIOCCL:IF> |
//</WIOCCL:FOREACH>
//:::';

$dataSource = [
    'semestre' => 1,
    'tipusModulBloc' => "mòdul",
    'itinerariRecomanatS1' => 'verd',
    'itinerariRecomanatS2' => 'cotxe',
    'dedicacio' => 8,
    'einesAprenentatge' => '[{
		"tipus": "aaaa",
		"eina": "bbb",
		"opcionalitat": "111",
		"puntuable": "true"
	},
	{
		"tipus": "jjj",
		"eina": "222",
		"opcionalitat": "rrr",
		"puntuable": "false"
	},
    {
		"tipus": "aaaa",
		"eina": "bbb",
		"opcionalitat": "111",
		"puntuable": "true"
	},
	{
		"tipus": "aaaa",
		"eina": "bbb",
		"opcionalitat": "111",
		"puntuable": "true"
	},
]',
    'datesAC' => '[{
        "unitat": "1",
        "test": "a",
		"enunciat": "2013/2/1",
		"lliurament": "2013/3/2",
		"solució": "2013/4/3",
		"qualificació": "2013/4/4"
	},
	{
		"unitat": "1",
		"test": "a",
		"enunciat": "2014/2/1",
		"lliurament": "2014/3/2",
		"solució": "2014/4/3",
		"qualificació": "4-4-2014"
	},
	{
		"unitat": "2",
		"test": "a",
		"enunciat": "2017/2/1",
		"lliurament": "2017/3/2",
		"solució": "2017/4/3",
		"qualificació": "4-4-2017"
	},
	{
		"unitat": "1",
		"test": "b",
		"enunciat": "2018/2/1",
		"lliurament": "2018/3/2",
		"solució": "2018/4/3",
		"qualificació": "4-4-2018"
	}
]'
];
//$t = '
//
//====== TEST: IF======
//<WIOCCL:IF condition="{##semestre##}==1">{##itinerariRecomanatS1##}</WIOCCL:IF><WIOCCL:IF condition="{##semestre##}==2">{##itinerariRecomanatS2##}</WIOCCL:IF>
//semestre de l\'itinerari formatiu i suposa una **dedicació setmanal mínima  de {##dedicacio##}h.**
//
//Per cursar aquest {##tipusModulBloc##} és requisit NO cursar simultàniamentDiria que hi ha 3 casos: (1)no cursar simultàniament, (2)cursar simultàniament o haver superat i (3)haver superat. En qualsevol cas manquen camps amb aquesta informació. És una inmnformació necessària a aquí?. Una solucó parcial seria reflectir tots els casos al camp requisit. Ho parlem haver superat els mòduls: {##requisits##} (en cas d\' idncompatibilitats) no entenc aquest parèntesi.
//
//El material que treballareu és el següent
//<WIOCCL:IF condition="{##tipusModulBloc##}==\'\'mòdul\'\'">
//  * XXXX
//  * XXXX
//  * XXXX
//</WIOCCL:IF>
//
//<WIOCCL:IF condition="{##tipusModulBloc##}==\'\'dul\'\'">
//  * NNNN
//  * NNNN
//  * NNNN
//</WIOCCL:IF>
//
//<WIOCCL:IF condition="{##tipusModulBloc##}!=\'\'mòdul\'\'">
//  * YYYY
//  * YYYY
//  * YYYY
//</WIOCCL:IF>
//
//<WIOCCL:IF condition="{##semestre##}\>0">
//funciona condition \'\'>\'\'
//</WIOCCL:IF>
//
//<WIOCCL:IF condition="{##semestre##}\>1">
//NO funciona condition \'\'>\'\'
//</WIOCCL:IF>
//
//<WIOCCL:IF condition="{##semestre##}\>=1">
//Funciona condition \'\'>=\'\'
//</WIOCCL:IF>
//
//<WIOCCL:IF condition="{##semestre##}\>=2">
//No funciona condition \'\'>=\'\'
//</WIOCCL:IF>
//
//<WIOCCL:IF condition="{##semestre##}\>=2&&{##tipusModulBloc##}==\'\'mòdul\'\'">
//No funciona condition \'\'AND\'\'
//</WIOCCL:IF>
//
//<WIOCCL:IF condition="{##semestre##}\<=2&&{##tipusModulBloc##}==\'\'mòdul\'\'">
//Funciona condition \'\'AND\'\'
//</WIOCCL:IF>
//
//<WIOCCL:IF condition="{##semestre##}\>=2||{##tipusModulBloc##}==\'\'mòdul\'\'">
//Funciona condition \'\'OR\'\'
//</WIOCCL:IF>
//
//<WIOCCL:IF condition="{##semestre##}\>=2||{##tipusModulBloc##}!=\'\'mòdul\'\'">
//No funciona condition \'\'OR\'\'
//</WIOCCL:IF>
//
//<WIOCCL:IF condition="{##semestre##}\>=2&&{##tipusModulBloc##}==\'\'mòdul\'\'||{##semestre##}\>=1&&{##tipusModulBloc##}!=\'\'res\'\'">
//Funciona condition \'\'AND OR\'\'
//</WIOCCL:IF>
//
//==== TEST INSERT ====
//<WIOCCL:INSERT ns="lalala:lelel:lilil"/>
//
//
//';

/* Test foreach amb filtre */
//$t = 'Les dates clau del semestre, que també podeu consultar al calendari de l\'aula, són les següents: (veure:table:TA1:).
//
//::table:TA1
//  :title:Dates clau
//  :type:io_pt
//  :footer::
//^  unitat  ^  data de publicació de l\'enunciat  ^ data de publicació de la solució ^ data de publicació de la qualificació ^
//<WIOCCL:FOREACH var="item" array="{##datesAC##}" filter="{##item[unitat]##}=={##testitem[unitat]##}">
//| U{##item[unitat]##} | {#_DATE("{##item[enunciat]##}")_#} | {#_DATE("{##item[lliurament]##}")_#} | {#_DATE("{##item[solució]##}")_#} | {#_DATE("{##item[qualificació]##}")_#} |
//</WIOCCL:FOREACH>
//:::
//
//::table:TA2
//  :title:Dates clau
//  :type:io_pt
//  :footer::
//^  unitat  ^  data de publicació de l\'enunciat  ^ data de publicació de la solució ^ data de publicació de la qualificació ^
//<WIOCCL:FOREACH var="item" array="{##datesAC##}" filter="{##item[enunciat]##}==\'\'2014/2/1\'\'">
//| U{##item[unitat]##} | {#_DATE("{##item[enunciat]##}")_#} | {#_DATE("{##item[lliurament]##}")_#} | {#_DATE("{##item[solució]##}")_#} | {#_DATE("{##item[qualificació]##}")_#} |
//</WIOCCL:FOREACH>
//:::
//Test array length: {#_ARRAY_LENGTH({##datesAC##})_#}
//Test count distinct: {#_COUNTDISTINCT({##datesAC##}, ["unitat", "test"])_#}
//
//<WIOCCL:FOR from="1" to="{#_COUNTDISTINCT({##datesAC##}, [\'\'unitat\'\'])_#}" counter="ind">
//^  unitat  ^  data de publicació de l\'enunciat  ^ data de publicació de la solució ^ data de publicació de la qualificació ^
//<WIOCCL:FOREACH var="item" array="{##datesAC##}" filter="{##item[unitat]##}=={##ind##}">
//| U{##item[unitat]##} | {#_DATE("{##item[enunciat]##}")_#} | {#_DATE("{##item[lliurament]##}")_#} | {#_DATE("{##item[solució]##}")_#} | {#_DATE("{##item[qualificació]##}")_#} |
//</WIOCCL:FOREACH>
//</WIOCCL:FOR>
//
//';

/* Test subset, first i last */

//$t = '
//<WIOCCL:SUBSET subsetvar="filtered" array="{##datesAC##}" arrayitem="itemsub" filter="{##testitem[unitat]##}=={##itemsub[unitat]##}">
//{#_FIRST({##filtered##}, "FIRST[enunciat]")_#}
//{#_FIRST({##filtered##}, "FIRST")_#}
//{#_FIRST({##filtered##}, "{\"a\":\"FIRST[enunciat]\", \"b\":5, \"c\":true, \"d\":\"{##semestre##}\", \"z\":\"FIRST[lliurament]\"}")_#}
//-
//{#_LAST({##filtered##}, "LAST[enunciat]")_#}
//{#_LAST({##filtered##}, "LAST")_#}
//{#_LAST({##filtered##}, "{\"a\":\"LAST[enunciat]\", \"b\":5, \"c\":true, \"d\":\"{##semestre##}\", \"z\":\"LAST[lliurament]\"}")_#}
//
//</WIOCCL:SUBSET>
//';
//$t = '!!TEST START!! <WIOCCL:IF condition="{##semestre##}==1">{##itinerariRecomanatS1##}</WIOCCL:IF>
////<WIOCCL:IF condition="{##semestre##}==2">{##itinerariRecomanatS2##}</WIOCCL:IF> !!TEST END!!';

$t = '==== TEST INSERT ====
<WIOCCL:INSERT ns="lalala:lelel:lilil"/>';

global $conf;


//$p = new WiocclParser($t,['testitem'=>['unitat'=>1]], $dataSource);
print_r('<pre>');
print_r(WiocclParser::getValue($t,['testitem'=>['unitat'=>1]], $dataSource));
print_r('</pre>');