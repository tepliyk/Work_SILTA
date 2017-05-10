<?
define('STOP_STATISTICS', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
$GLOBALS['APPLICATION']->RestartBuffer();
require $_SERVER['DOCUMENT_ROOT'].'/silta_components/index.php';
set_time_limit(720);

$function_type    = $_GET["function_type"];
$PROPERTY_CODE_1C = 'code_1c';
$DATAFILE_NAME    = 'datafile';
/* ============================================================================================== */
/* ==================================== МАССИВ СООТВЕТСТВИЙ ===================================== */
/* ============================================================================================== */
$main_mass = array
	(
	"contragents" => array
		(
		"code"  => 'contragents',
		"props" => array
			(
			"name"       => array("bitrix_code" => 'name',               "1c_code" => 'NAME'),
			"full_name"  => array("bitrix_code" => 'full_name',          "1c_code" => 'FULL_NAME'),
			"inn"        => array("bitrix_code" => 'inn',                "1c_code" => 'INN'),
			"okpo"       => array("bitrix_code" => 'okpo',               "1c_code" => 'OKPO'),
			"doc_number" => array("bitrix_code" => 'certificate_number', "1c_code" => 'DOC_NUMBER'),
			"country"    => array("bitrix_code" => 'country',            "1c_code" => 'COUNTRY'),
			"ur_adress"  => array("bitrix_code" => 'ur_adress',          "1c_code" => 'UR_ADDRESS'),
			"fiz_adress" => array("bitrix_code" => 'fiz_adress',         "1c_code" => 'FIZ_ADDRESS'),
			),
		),
	"trade_marks" => array
		(
		"code" => 'goods-trade_marks',
		),
	"nomenclature" => array
		(
		"code"  => 'goods-nomenclature',
		"props" => array
			(
			"1c_name" => array("bitrix_code" => 'name_1c',    "1c_code" => 'NAME'),
			"parent"  => array("bitrix_code" => 'trade_mark', "1c_code" => 'PARENT'),
			"value"   => array("bitrix_code" => 'nominal',    "1c_code" => 'VALUE'),
			"volume"  => array("bitrix_code" => 'packing',    "1c_code" => 'VOLUME'),
			"set"     => array("bitrix_code" => 'set',        "1c_code" => 'SET'),
			"articul" => array("bitrix_code" => 'articul',    "1c_code" => 'ARTICUL'),
			),
		),
	"price" => array
		(
		"code"  => 'goods-price',
		"props" => array
			(
			"nomenclature" => array("bitrix_code" => 'nomenclature', "1c_code" => 'ID'),
			"price_type"   => array("bitrix_code" => 'price_type',   "1c_code" => 'TYPE'),
			"price"        => array("bitrix_code" => 'price',        "1c_code" => 'PRICE'),
			"date"         => array("bitrix_code" => 'date',         "1c_code" => 'DATE'),
			),

		"price_type_table" => array
			(
			"1" => 'rrc',
			),
		),
	"discount" => array
		(
		"code"  => 'goods-discount',
		"props" => array
			(
			"contragent" => array("bitrix_code" => 'contragent', "1c_code" => 'CONTRAGENT'),
			"trade_mark" => array("bitrix_code" => 'trade_mark', "1c_code" => 'TRADE_MARK'),
			"discount"   => array("bitrix_code" => 'discount',   "1c_code" => 'DISCOUNT'),
			"date"       => array("bitrix_code" => 'date',       "1c_code" => 'DATE'),
			),
		),
	);
/* ============================================================================================== */
/* ================================== СОЗДАНИЕ ОБЪЕКТОВ ТАБЛИЦ ================================== */
/* ============================================================================================== */
foreach($main_mass as $table => $table_mass)
	{
	$TABLE[$table] = new IBLOCK_TABLE(array("code" => $table_mass["code"]));

	$table_props = array("name", $PROPERTY_CODE_1C);
	foreach($main_mass[$table]["props"] as $property => $property_mass)
		{
		$TABLE[$table]->rename_property($property_mass["bitrix_code"], $property);
		$table_props[] = $property;
		}

	$TABLE[$table]->refresh_property($table_props);
	}
/* ============================================================================================== */
/* ==================================== НОМЕНКЛАТУРА ТОВАРОВ ==================================== */
/* ============================================================================================== */
if($function_type == 'get_nomenclature')
	{
	$goods_mass = recieved_xml_read($_FILES[$DATAFILE_NAME], $main_mass["nomenclature"]["props"]);
	foreach($TABLE["nomenclature"]->get_property() as $property => $property_object) $table_props_main[] = $property;
	$table_props_alt = array("1c_name", "parent", "set");
	/* -------------------------------------------------------------------- */
	/* ------------------------ проход по массиву ------------------------- */
	/* -------------------------------------------------------------------- */
	if($goods_mass)
		foreach($goods_mass as $element_id => $element_mass)
			{
			/* ----------------------------------------- */
			/* --------------- свойства ---------------- */
			/* ----------------------------------------- */
			unset($NAME, $value, $volume, $set, $parent);
			$NAME   = $element_mass["1c_name"][0];
			$value  = $element_mass["value"]  [0];
			$volume = $element_mass["volume"] [0];
			/* ----------------------------------------- */
			/* ---------------- комплект --------------- */
			/* ----------------------------------------- */
			if($element_mass["set"][0])
				{
				unset($set_name_mass);
				$value  = '';
				$volume = '';

				foreach($element_mass["set"] as $element_id_1c)
					{
					$TABLE["nomenclature"]->unset_element();
					$TABLE["nomenclature"]->unset_query_options();
					$TABLE["nomenclature"]->set_query_options(array("filter" => array($PROPERTY_CODE_1C => $element_id_1c)));
					$TABLE["nomenclature"]->set_element();

					foreach($TABLE["nomenclature"]->get_element() as $set_element_id => $element_object)
						{
						$set[] = $set_element_id;
						$set_name_mass[] = $element_object->get_property("name")->get_value().' '.$element_object->get_property("value")->get_value().$element_object->get_property("volume")->get_value();
						}
					}

				$set_name_mass = array_count_values($set_name_mass);
				foreach($set_name_mass as $value => $count) $set_name_mass[$value] = $value.' ('.$count.' шт.)';
				$NAME = implode($set_name_mass, ' + ');
				}
			/* ----------------------------------------- */
			/* ---------------- поиск ТМ --------------- */
			/* ----------------------------------------- */
			unset($TM_NAME);
			$TABLE["trade_marks"]->unset_element();
			$TABLE["trade_marks"]->set_query_options(array("filter" => array($PROPERTY_CODE_1C => $element_mass["parent"][0])));
			$TABLE["trade_marks"]->set_element();

			foreach($TABLE["trade_marks"]->get_element() as $tm_element_id => $element_object)
				{
				$TM_NAME = $element_object->get_property("name")->get_value();
				$parent  = $tm_element_id;
				}
			if(!$parent) continue;
			/* ----------------------------------------- */
			/* --------- поиск такого элемента --------- */
			/* ----------------------------------------- */
			$TABLE["nomenclature"]->unset_element();
			$TABLE["nomenclature"]->unset_query_options();
			$TABLE["nomenclature"]->set_query_options(array("filter" => array($PROPERTY_CODE_1C => $element_id)));
			$TABLE["nomenclature"]->set_element();

			unset($NOMENCLATURE_ELEMENT);
			foreach($TABLE["nomenclature"]->get_element() as $element_object) $NOMENCLATURE_ELEMENT = $element_object;
			if(!$NOMENCLATURE_ELEMENT)
				{
				$NOMENCLATURE_ELEMENT = new TABLE_ELEMENT(array("iblock" => $TABLE["nomenclature"], "element_id" => 'new'));
				$TABLE_PROPS = $table_props_main;
				}
			else
				$TABLE_PROPS = $table_props_alt;
			/* ----------------------------------------- */
			/* ------------- для разных ТМ ------------- */
			/* ----------------------------------------- */
			if(!$set[0])
				{
				if($TM_NAME == 'ELEMENT')
					{
					// парсинг названия
					$name_rearch = preg_match('/\(.*\)/', $NAME, $search_mass);
					if($name_rearch)
						foreach($search_mass as $search)
							if(preg_match('/\d(\d|\.|,)*/', $search, $number_found) && !$number_search)
								{
								if(substr_count($search, 'кг')) $volume = 'кг';
								if(substr_count($search, 'л'))  $volume = 'л';
								$value = $number_found[0];
								$NAME = str_replace(' '.$search, '', $NAME);
								}

					$NAME = str_replace(', ', ' ', $NAME);
					// корректировки
					if($volume == 'бан')   $volume = 'л';
					if($value && !$volume) $volume = 'л';

					$NAME = str_replace(array('Елемент', 'Элемент'), 'Element', $NAME);
					$NAME = str_replace(array('Аква антисептик', 'аква антисептик'), 'Aqua Antiseptik', $NAME);
					$NAME = str_replace(array('econom', 'економ'), 'Econom', $NAME);
					$NAME = str_replace(array('Грунт Антисептик', 'грунт антисептик', 'Грунт антисептик', 'грунт Антисептик'), 'Grund Antiseptik', $NAME);
					$NAME = str_replace(array('Грунт', 'грунт'), 'Grund', $NAME);
					$NAME = str_replace(array(' MGF', ' МГФ'), '', $NAME);
					if(!$NAME) $NAME = $element_mass["1c_name"][0];
					}
				/* ------------------------- */
				if($TM_NAME == 'Harris')
					{
					if($value && !$value) $value = 'шт';
					}
				}
			/* ----------------------------------------- */
			/* ------------- корректировки ------------- */
			/* ----------------------------------------- */
			$value = str_replace(',', '.', $value) + 0;

			if($value == '0')                    unset($value);
			if(!$value)                          unset($volume);
			if(!$volume)                         unset($value);
			if($value == '1' && $volume == 'шт') unset($value, $volume);
			/* ----------------------------------------- */
			/* ----------- сохранение объекта ---------- */
			/* ----------------------------------------- */
			$NOMENCLATURE_ELEMENT->get_property($PROPERTY_CODE_1C)->set_user_value($element_id);
			$NOMENCLATURE_ELEMENT->get_property("name")   ->set_user_value($NAME);
			$NOMENCLATURE_ELEMENT->get_property("value")  ->set_user_value($value);
			$NOMENCLATURE_ELEMENT->get_property("volume") ->set_user_value($volume);
			$NOMENCLATURE_ELEMENT->get_property("parent") ->set_user_value($parent);
			$NOMENCLATURE_ELEMENT->get_property("set")    ->set_user_value($set);
			$NOMENCLATURE_ELEMENT->get_property("1c_name")->set_user_value($element_mass["1c_name"]);
			$NOMENCLATURE_ELEMENT->get_property("articul")->set_user_value($element_mass["articul"]);
			$NOMENCLATURE_ELEMENT->save_element($TABLE_PROPS);
			}

	if(!$goods_mass) echo 'error';
	else             echo 'success';
	}
/* ============================================================================================== */
/* ======================================== ТОВАРЫ- ЦЕНЫ ======================================== */
/* ============================================================================================== */
if($function_type == 'get_price')
	{
	$price_mass = recieved_xml_read($_FILES[$DATAFILE_NAME], $main_mass["price"]["props"]);
	$price_type_list = $TABLE["price"]->get_property("price_type")->get_attributes("list");
	/* -------------------------------------------------------------------- */
	/* ------------------------ проход по массиву ------------------------- */
	/* -------------------------------------------------------------------- */
	if($price_mass)
		foreach($price_mass as $nomenclature_id => $element_mass)
			{
			/* ----------------------------------------- */
			/* --------------- свойства ---------------- */
			/* ----------------------------------------- */
			unset($NAME, $price_type, $date, $price);
			$price_type = $price_type_list
				[
				$main_mass["price"]["price_type_table"]
					[
					$element_mass["price_type"][0]
					]
				];                                                       // массив типа цены (Bitrix)
			$date  = date('d.m.Y', strtotime($element_mass["date"][0])); // дата
			$price = $element_mass["price"][0];                          // цена
			/* ----------------------------------------- */
			/* ---------- поиск номенклатуры ----------- */
			/* ----------------------------------------- */
			unset($NOMENCLATURE);
			$TABLE["nomenclature"]->unset_element();
			$TABLE["nomenclature"]->set_query_options(array("filter" => array($PROPERTY_CODE_1C => $nomenclature_id)));
			$TABLE["nomenclature"]->set_element();
			foreach($TABLE["nomenclature"]->get_element() as $element_object) $NOMENCLATURE = $element_object;
			if(!$NOMENCLATURE) continue;
			$NAME = str_replace(array('"', '&quot;'), '"', $NOMENCLATURE->get_property("name")->get_value().' - '.$price_type["title"]);
			/* ----------------------------------------- */
			/* --------- поиск такого элемента --------- */
			/* ----------------------------------------- */
			unset($PRICE_ELEMENT);
			$TABLE["price"]->unset_element();
			$TABLE["price"]->set_query_options(array("filter" => array("nomenclature" => $NOMENCLATURE->get_element_id(), "price_type" => $price_type["value"])));
			$TABLE["price"]->set_element();

			foreach($TABLE["price"]->get_element() as $element_object)
				if($element_object->get_property("date")->get_value() == $date)
					$PRICE_ELEMENT = $element_object;
			if(!$PRICE_ELEMENT) $PRICE_ELEMENT = new TABLE_ELEMENT(array("iblock" => $TABLE["price"], "element_id" => 'new'));
			/* ----------------------------------------- */
			/* ---------- сохранение объекта ----------- */
			/* ----------------------------------------- */
			$PRICE_ELEMENT->get_property("name")        ->set_user_value($NAME);
			$PRICE_ELEMENT->get_property("nomenclature")->set_user_value($NOMENCLATURE->get_element_id());
			$PRICE_ELEMENT->get_property("price_type")  ->set_user_value($price_type["code"]);
			$PRICE_ELEMENT->get_property("price")       ->set_user_value($price);
			$PRICE_ELEMENT->get_property("date")        ->set_user_value($date);
			$PRICE_ELEMENT->save_element();
			}

	if(!$price_mass) echo 'error';
	else             echo 'success';
	}
/* ============================================================================================== */
/* ======================================== ТОВАРЫ- ЦЕНЫ ======================================== */
/* ============================================================================================== */
if($function_type == 'get_contragents')
	{
	$contragents_mass = recieved_xml_read($_FILES[$DATAFILE_NAME], $main_mass["contragents"]["props"]);
	/* -------------------------------------------------------------------- */
	/* ------------------------ проход по массиву ------------------------- */
	/* -------------------------------------------------------------------- */
	if($contragents_mass)
		foreach($contragents_mass as $contragent_id => $element_mass)
			{
			/* ----------------------------------------- */
			/* --------- поиск такого элемента --------- */
			/* ----------------------------------------- */
			unset($CONTRAGENT_ELEMENT);
			$TABLE["contragents"]->unset_element();
			$TABLE["contragents"]->unset_query_options();
			$TABLE["contragents"]->set_query_options(array("filter" => array($PROPERTY_CODE_1C => $contragent_id)));
			$TABLE["contragents"]->set_element();

			foreach($TABLE["contragents"]->get_element() as $element_object) $CONTRAGENT_ELEMENT = $element_object;
			if(!$CONTRAGENT_ELEMENT) $CONTRAGENT_ELEMENT = new TABLE_ELEMENT(array("iblock" => $TABLE["contragents"], "element_id" => 'new'));
			/* ----------------------------------------- */
			/* ---------- сохранение объекта ----------- */
			/* ----------------------------------------- */
			foreach($main_mass["contragents"]["props"] as $property => $property_mass)
				$CONTRAGENT_ELEMENT->get_property($property)->set_user_value($element_mass[$property]);
			$CONTRAGENT_ELEMENT->get_property($PROPERTY_CODE_1C)->set_user_value($contragent_id);
			$CONTRAGENT_ELEMENT->save_element();
			}

	if(!$contragents_mass) echo 'error';
	else                   echo 'success';
	}
/* ============================================================================================== */
/* ======================================== ТОВАРЫ- ЦЕНЫ ======================================== */
/* ============================================================================================== */
if($function_type == 'get_discount')
	{
	$discount_mass = recieved_xml_read($_FILES[$DATAFILE_NAME], $main_mass["discount"]["props"]);
	/* -------------------------------------------------------------------- */
	/* ------------------------ проход по массиву ------------------------- */
	/* -------------------------------------------------------------------- */
	if($discount_mass)
		foreach($discount_mass as $element_mass)
			{
			/* ----------------------------------------- */
			/* ----------- поиск контрагента ----------- */
			/* ----------------------------------------- */
			unset($CONTRAGENT_ELEMENT);
			$TABLE["contragents"]->unset_element();
			$TABLE["contragents"]->unset_query_options();
			$TABLE["contragents"]->set_query_options(array("filter" => array($PROPERTY_CODE_1C => $element_mass["contragent"][0])));
			$TABLE["contragents"]->set_element();
			foreach($TABLE["contragents"]->get_element() as $element_object) $CONTRAGENT_ELEMENT = $element_object;
			if(!$CONTRAGENT_ELEMENT) continue;
			/* ----------------------------------------- */
			/* ---------- поиск торговой марки --------- */
			/* ----------------------------------------- */
			unset($TM_ELEMENT);
			$TABLE["trade_marks"]->unset_element();
			$TABLE["trade_marks"]->unset_query_options();
			$TABLE["trade_marks"]->set_query_options(array("filter" => array($PROPERTY_CODE_1C => $element_mass["trade_mark"][0])));
			$TABLE["trade_marks"]->set_element();
			foreach($TABLE["trade_marks"]->get_element() as $element_object) $TM_ELEMENT = $element_object;
			if(!$TM_ELEMENT) continue;
			/* ----------------------------------------- */
			/* --------- поиск такого элемента --------- */
			/* ----------------------------------------- */
			unset($DISCOUNT_ELEMENT);
			$TABLE["discount"]->unset_element();
			$TABLE["discount"]->unset_query_options();
			$TABLE["discount"]->set_query_options(array("filter" => array
				(
				"contragent" => $CONTRAGENT_ELEMENT->get_element_id(),
				"trade_mark" => $TM_ELEMENT->get_element_id()
				)));
			$TABLE["discount"]->set_element();

			foreach($TABLE["discount"]->get_element() as $element_object)
				if($element_object->get_property("date")->get_value() == $element_mass["date"][0])
					$DISCOUNT_ELEMENT = $element_object;
			if(!$DISCOUNT_ELEMENT) $DISCOUNT_ELEMENT = new TABLE_ELEMENT(array("iblock" => $TABLE["discount"], "element_id" => 'new'));
			/* ----------------------------------------- */
			/* ---------- сохранение объекта ----------- */
			/* ----------------------------------------- */
			$DISCOUNT_ELEMENT->get_property("name")      ->set_user_value("скидка");
			$DISCOUNT_ELEMENT->get_property("contragent")->set_user_value($CONTRAGENT_ELEMENT->get_element_id());
			$DISCOUNT_ELEMENT->get_property("trade_mark")->set_user_value($TM_ELEMENT->get_element_id());
			$DISCOUNT_ELEMENT->get_property("discount")  ->set_user_value($element_mass["discount"][0]);
			$DISCOUNT_ELEMENT->get_property("date")      ->set_user_value($element_mass["date"][0]);
			$DISCOUNT_ELEMENT->save_element();
			}

	if(!$discount_mass) echo 'error';
	else                echo 'success';
	}
?>