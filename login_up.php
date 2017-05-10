<?

namespace Silta;
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php";
$APPLICATION->SetTitle("Login up");

if (isset($_POST["login"])) {
    $value = IBLOCK_ELEMENT::get_ghost_property("user")->set_value($_POST["login_by"], "form")->get_value();

    if ($value != 1003) {
        $USER->Authorize($value);
        header('Location:/company/personal/user/' . $value . '/');
    } else {
        echo "<font color=\"red\"><strong>Запрещено авторизоваться под этим пользователем</strong></font><br><br>";
    }
}

echo '
	<form method="post">
		<div>' . (new FORM_BUILDER)->get_element("user_selector")->set_options(["input_name" => 'login_by'])->build() . '</div>
		' . (new FORM_BUILDER)->get_element("button")->set_options(["name" => 'login', "title" => 'Залогиниться', "tag" => 'button'])->build() . '
	</form>';
?>