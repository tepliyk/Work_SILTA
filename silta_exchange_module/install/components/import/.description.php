<?
if(!CModule::IncludeModule("silta_exchange_module")) return;
$arComponentDescription =
	[
	"NAME"        => GetMessage("SEM_I_COMPONENT_NAME"),
	"DESCRIPTION" => GetMessage("SEM_I_COMPONENT_DESCRIPTION"),
	'PATH'        =>
		[
		'ID'   => 'silta_exchange_module',
		"NAME" => GetMessage("SEM_NAME")
		]
	];
?>