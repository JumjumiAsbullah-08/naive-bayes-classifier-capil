<?php
session_start();
require_once "../../koneksi.php";
// Periksa apakah user sudah login dan memiliki peran sebagai admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Koneksi ke database
$conn = connectDatabase();

?>
<link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />
<div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
        <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
            <h6 class="text-white text-capitalize ps-3">Likelihood</h6>
        </div>
    </div>
    <div class="card-body px-0 pb-2">
        <div class="table-responsive p-0">
            <table class="table table-hover align-items-center mb-0">
                <thead>
                    <tr>
                        <th class="text-center">Kriteria</th>
                        <th class="text-center">Sub Kriteria</th>
                        <th class="text-center">Likelihood Naik</th>
                        <th class="text-center">Likelihood Tidak Naik</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch criteria
                    $sql_criteria = "SELECT id, name FROM criteria";
                    $result_criteria = $conn->query($sql_criteria);

                    if ($result_criteria->num_rows > 0) {
                        // Loop through each criteria
                        while ($row_criteria = $result_criteria->fetch_assoc()) {
                            $criteria_id = $row_criteria['id'];
                            $criteria_name = $row_criteria['name'];

                            // Fetch sub-criteria
                            $sql_sub_criteria = "
                                SELECT sc.id AS sub_criteria_id, sc.name AS sub_criteria_name
                                FROM sub_criteria sc
                                LEFT JOIN karyawan_kriteria kk ON sc.id = kk.sub_criteria_id
                                WHERE kk.criteria_id = $criteria_id
                                GROUP BY sc.id, sc.name
                            ";
                            $result_sub_criteria = $conn->query($sql_sub_criteria);

                            if ($result_sub_criteria->num_rows > 0) {
                                // Loop through each sub-criteria
                                while ($row_sub_criteria = $result_sub_criteria->fetch_assoc()) {
                                    $sub_criteria_id = $row_sub_criteria['sub_criteria_id'];
                                    $sub_criteria_name = $row_sub_criteria['sub_criteria_name'];

                                    // Count occurrences for "Naik"
                                    $sql_count_naik = "
                                        SELECT COUNT(*) AS count_naik
                                        FROM karyawan_kriteria kk
                                        LEFT JOIN training_data td ON kk.karyawan_id = td.karyawan_id
                                        WHERE kk.sub_criteria_id = $sub_criteria_id AND td.status = 'Naik'
                                    ";
                                    $result_count_naik = $conn->query($sql_count_naik);
                                    $count_naik = $result_count_naik->fetch_assoc()['count_naik'];

                                    // Count total "Naik"
                                    $sql_total_naik = "
                                        SELECT COUNT(*) AS total_naik
                                        FROM training_data
                                        WHERE status = 'Naik'
                                    ";
                                    $result_total_naik = $conn->query($sql_total_naik);
                                    $total_naik = $result_total_naik->fetch_assoc()['total_naik'];

                                    // Count occurrences for "Tidak Naik"
                                    $sql_count_tidak_naik = "
                                        SELECT COUNT(*) AS count_tidak_naik
                                        FROM karyawan_kriteria kk
                                        LEFT JOIN training_data td ON kk.karyawan_id = td.karyawan_id
                                        WHERE kk.sub_criteria_id = $sub_criteria_id AND td.status != 'Naik'
                                    ";
                                    $result_count_tidak_naik = $conn->query($sql_count_tidak_naik);
                                    $count_tidak_naik = $result_count_tidak_naik->fetch_assoc()['count_tidak_naik'];

                                    // Count total "Tidak Naik"
                                    $sql_total_tidak_naik = "
                                        SELECT COUNT(*) AS total_tidak_naik
                                        FROM training_data
                                        WHERE status != 'Naik'
                                    ";
                                    $result_total_tidak_naik = $conn->query($sql_total_tidak_naik);
                                    $total_tidak_naik = $result_total_tidak_naik->fetch_assoc()['total_tidak_naik'];

                                    // Calculate likelihood for "Naik"
                                    $likelihood_naik = ($total_naik > 0) ? $count_naik / $total_naik : 0;

                                    // Calculate likelihood for "Tidak Naik"
                                    $likelihood_tidak_naik = ($total_tidak_naik > 0) ? $count_tidak_naik / $total_tidak_naik : 0;

                                    // Display results for each sub-criteria in a table row
                                    echo "<tr>";
                                    echo "<td class='text-center'>$criteria_name</td>";
                                    echo "<td class='text-center'>$sub_criteria_name</td>";
                                    echo "<td class='text-center'>" . number_format($likelihood_naik, 4) . "</td>";
                                    echo "<td class='text-center'>" . number_format($likelihood_tidak_naik, 4) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                // If no sub-criteria found for criteria
                                echo "<tr><td colspan='4' class='text-center'>No sub-criteria found for criteria ID $criteria_id</td></tr>";
                            }
                        }
                    } else {
                        // If no criteria found
                        echo "<tr><td colspan='4' class='text-center'>No criteria found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
