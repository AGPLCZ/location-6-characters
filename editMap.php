<?php
require_once "../config.php"; // Připojení k databázi pomocí MeekroDB

// **Získání dat školy pro editaci**
$edit_school = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_school"])) {
    $edit_school = DB::queryFirstRow("SELECT * FROM school_data WHERE school_id=%i", $_POST['school_id']);
}

// **Přidání nebo úprava školy**
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_school"])) {
    $data = [
        'school_name' => $_POST['school_name'],
        'locationCode' => $_POST['locationCode'],
        'address' => $_POST['address'],
        'web' => $_POST['web'],
        'tel' => $_POST['tel'],
        'licence' => $_POST['licence'],
        'vek' => $_POST['vek'],
        'dem_rozhodovani' => $_POST['dem_rozhodovani'],
        'resp_komunikace' => $_POST['resp_komunikace'],
        'resp_pristup' => $_POST['resp_pristup'],
        'pov_ucivo' => $_POST['pov_ucivo'],
        'hodnoceni' => $_POST['hodnoceni'],
        'vyuc_hodiny' => $_POST['vyuc_hodiny'],
        'pov_dochazka' => $_POST['pov_dochazka'],
        'odchod_budova' => $_POST['odchod_budova'],
        'pov_cinnosti' => $_POST['pov_cinnosti'],
        'role_dospeleho' => $_POST['role_dospeleho'],
        'pravidla_hranice' => $_POST['pravidla_hranice'],
        'soc_interakce' => $_POST['soc_interakce'],
        'zpusob_prace' => $_POST['zpusob_prace'],
        'org_prostoru' => $_POST['org_prostoru'],
        'indiv_studium' => $_POST['indiv_studium'],
        'spolucast_deti' => $_POST['spolucast_deti'],
        'stravovani' => $_POST['stravovani'],
        'stat_osnovy' => $_POST['stat_osnovy']
    ];

    if (!empty($_POST['school_id'])) {
        DB::update('school_data', $data, "school_id=%i", $_POST['school_id']);
    } else {
        DB::insert('school_data', $data);
    }
}

// **Smazání školy**
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_school"])) {
    DB::delete('school_data', "school_id=%i", $_POST['school_id']);
}

// **Načtení všech škol**
$schools = DB::query("SELECT * FROM school_data");
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editace mapy škol</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
    <h2 class="mb-4"><?= $edit_school ? "Upravit školu" : "Přidat novou školu" ?></h2>
    
    <form method="POST" class="mb-4">
        <input type="hidden" name="school_id" value="<?= $edit_school['school_id'] ?? '' ?>">

        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Název školy</label>
                <input type="text" name="school_name" class="form-control" value="<?= $edit_school['school_name'] ?? '' ?>">
            </div>
            <div class="form-group col-md-6">
                <label>Kód lokace</label>
                <input type="text" name="locationCode" class="form-control" value="<?= $edit_school['locationCode'] ?? '' ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Adresa</label>
                <input type="text" name="address" class="form-control" value="<?= $edit_school['address'] ?? '' ?>">
            </div>
            <div class="form-group col-md-6">
                <label>Web</label>
                <input type="text" name="web" class="form-control" value="<?= $edit_school['web'] ?? '' ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Telefon</label>
                <input type="text" name="tel" class="form-control" value="<?= $edit_school['tel'] ?? '' ?>">
            </div>
            <div class="form-group col-md-6">
                <label>Zapsána v rejstříku škol</label>
                <input type="text" name="licence" class="form-control" value="<?= $edit_school['licence'] ?? '' ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Typ školy</label>
            <input type="text" name="vek" class="form-control" value="<?= $edit_school['vek'] ?? '' ?>">
        </div>

        <!-- Další parametry -->
        <?php
        $params = [
            "dem_rozhodovani" => "Demokratické rozhodování",
            "resp_komunikace" => "Respektující komunikace",
            "resp_pristup" => "Respektující přístup",
            "pov_ucivo" => "Povinné učivo",
            "hodnoceni" => "Probíhá hodnocení",
            "vyuc_hodiny" => "Vyučovací hodiny",
            "pov_dochazka" => "Povinná docházka",
            "odchod_budova" => "Možnost odcházet z budovy",
            "pov_cinnosti" => "Povinné činnosti",
            "role_dospeleho" => "Role dospělého",
            "pravidla_hranice" => "Pravidla a hranice",
            "soc_interakce" => "Sociální interakce",
            "zpusob_prace" => "Způsob práce",
            "org_prostoru" => "Organizace prostoru",
            "indiv_studium" => "Možnost individuálního studia",
            "spolucast_deti" => "Spoluúčast dětí na chodu školy",
            "stravovani" => "Stravování",
            "stat_osnovy" => "Přítomnost státních osnov"
        ];

        foreach ($params as $key => $label) {
            echo "<div class='form-group'>";
            echo "<label>$label</label>";
            echo "<input type='text' name='$key' class='form-control' value='" . ($edit_school[$key] ?? '') . "'>";
            echo "</div>";
        }
        ?>

        <button type="submit" name="save_school" class="btn btn-primary"><?= $edit_school ? "Uložit změny" : "Přidat školu" ?></button>
    </form>

    <h2 class="mb-4">Seznam škol</h2>
    <table class="table table-bordered">
        <thead>
            <tr><th>ID</th><th>Název školy</th><th>Adresa</th><th>Web</th><th>Akce</th><th>Akce</th></tr>
        </thead>
        <tbody>
            <?php foreach ($schools as $school): ?>
                <tr>
                <td><?= htmlspecialchars($school['school_id']) ?></td>
                    <td><?= htmlspecialchars($school['school_name']) ?></td>
                    <td><?= htmlspecialchars($school['address']) ?></td>
                    <td><a href="<?= htmlspecialchars($school['web']) ?>" target="_blank"><?= htmlspecialchars($school['web']) ?></a></td>
                    <td><form method="POST"><input type="hidden" name="school_id" value="<?= $school['school_id'] ?>"><button type="submit" name="edit_school" class="btn btn-warning btn-sm">Editovat</button></form></td>
                    <td>
                    <form method="POST" style="display:inline;">
                            <input type="hidden" name="school_id" value="<?= $school['school_id'] ?>">
                            <button type="submit" name="delete_school" class="btn btn-danger btn-sm" onclick="return confirm('Opravdu chcete smazat tuto školu?');">Smazat</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>


