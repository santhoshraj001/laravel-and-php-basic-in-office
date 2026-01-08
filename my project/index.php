
<?php //php ajax handler 
if (isset($_GET['ajax_sort']) && $_GET['ajax_sort'] === "id") {

    $order = ($_GET['order'] ?? 'asc') === "desc" ? "DESC" : "ASC";
    $conn = mysqli_connect("localhost", "root", "", "mydb");
    $res = mysqli_query($conn, "SELECT * FROM students ORDER BY id $order LIMIT 10"); // limit to 10 for speed

    while ($row = mysqli_fetch_assoc($res)) {
        $photos = json_decode($row['photo'], true) ?: [];
        echo "<tr>
                <td><input type='checkbox' name='selected[]' value='".intval($row['id'])."'></td>
                <td>".intval($row['id'])."</td>
                <td>".htmlspecialchars($row['name'])."</td>
                <td>".htmlspecialchars($row['email'])."</td>
                <td>".htmlspecialchars($row['phone'])."</td>
                <td>".htmlspecialchars($row['department'])."</td>
                <td>".htmlspecialchars($row['domain'])."</td>
                <td>";
        if ($photos) {
            foreach ($photos as $p) {
                $pth = "uploads/" . $p;
                if (file_exists($pth)) echo "<img src='".htmlspecialchars($pth)."' class='table-thumb'>";
                else echo '<span style="color:#c33">(missing)</span>';
            }
        } else {
            echo '<span style="color:#666;">(none)</span>';
        }
        echo "</td>
              <td><button type='button' class='view-btn' onclick='showDetails(".json_encode(htmlspecialchars($row["name"])).",".json_encode(htmlspecialchars($row["email"])).",".json_encode(htmlspecialchars($row["phone"])).",".json_encode(htmlspecialchars($row["department"])).",".json_encode(htmlspecialchars($row["domain"])).",".json_encode($photos).")'>View</button></td>
              <td><button name='edit_single' value='".intval($row['id'])."' class='edit-btn'>Edit</button></td>
              <td><button name='delete_single' value='".intval($row['id'])."' class='delete-btn'>Delete</button></td>
              </tr>";
    }

    exit(); // stop full page
}
?>

<?php
/* index.php - cleaned & fixed version and its used to find the error on 
the php code after run in browser its show error its incase have */
ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start();
//mysql connection line for php to database
$conn = mysqli_connect("localhost", "root", "", "mydb");
if (!$conn) {
    die("DB connect error: " . mysqli_connect_error());
}

/* helper to sanitize filenames and its create _ for unwanted syntax 
in  file name like '%$#*)'*/
function clean_filename($name) {
    return preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
}

// Messages & UI flags
$message = "";   //This variable is used to store text messages for the user.
$showTable = false; //This is a flag (true/false variable)Do NOT show the table yet.If user clicks "search" → $showTable = true;
$editMode = false; //User is NOT editing a record.Control if the page is doing ADD or EDIT.
$editData = []; //This stores the existing data when a user clicks "Edit".Hold the data of the row being edited.

/* =========================
   SAVE (INSERT)
========================= */
/* mysqli_real_escape_string - this escaping method used to Removes dangerous characters 
store the file name safely its avoid sql injection other somes.
its adds a slash (\) before dangerous characters to make them safe. */
if (isset($_POST['save_data'])) {
    $name   = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $email  = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $phone  = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $dept   = mysqli_real_escape_string($conn, $_POST['department'] ?? '');
    $domain = mysqli_real_escape_string($conn, $_POST['domain'] ?? '');

    $uploaded_files = [];
    /* !empty means=its not empty value and 
    isset = that variable is exist and have not a null value */
    if (!empty($_FILES['photos']['name'][0])) {
        $count_files = count($_FILES['photos']['name']);
        if ($count_files > 5) {
            $message = "❌ You can upload a maximum of 5 photos.";
        } else {
            /*its count the file as [0,1,2,3,4]
             this type of increment , array start index num is 0 */
            for ($i = 0; $i < $count_files; $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) { //this line explain this upload is not error
                    $orig = basename($_FILES['photos']['name'][$i]); //its take original file name of photos 
                    /* then its create unique string_photo name for 
                    avoid file name error in save same file multiple time*/
                    $safe = uniqid() . "_" . clean_filename($orig); //its create uniq staring string_filename
                    $dest = __DIR__ . "/uploads/" . $safe;  // its used to store file in correct directory
                    /* php store the uploaded files temporaily location and then its
                     go the correct destination of uploads folder */
                    if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $dest)) { //$dest means where you want to save the file
                        $uploaded_files[] = $safe;
                    }
                }
            }
        }
    }

    if ($message === "") {  // (=== means both values are same and same datatype)
        $photos_json = json_encode($uploaded_files);/* $uploaded_files is an array of uploaded photo names eg ["a.jpg", "b.png"].
         json_encode() converts array → JSON string.*/
        // Insert and include created_at default on DB side
        $sql = "INSERT INTO students (name,email,phone,department,domain,photo) VALUES ('$name','$email','$phone','$dept','$domain','$photos_json')";
        if (mysqli_query($conn, $sql)) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?saved=1"); /* header("Location: ...") = Redirect to another page.So PHP sends a header saying:"Go to this URL."
            and $_SERVER['PHP_SELF'] =The same file you are currently running. eg (index.php run again using php_self) */
            exit(); // its prevents form resubmission on refresh.
        } else {
            $message = "❌ Insert Error: " . mysqli_error($conn);
        }
    }
}

/* If redirected after save, show success message and table */
if (isset($_GET['saved']) && $_GET['saved'] == 1) { /* && =This means both conditions must be TRUE and
     If saved exists in the URL AND its value is equal to 1, then run the code inside.*/
    //$_GET is a PHP superglobal array that collects values from the URL.
    $message = "✔ Data Saved Successfully!";
    $showTable = true;
}

/* =========================
   DOWNLOAD SELECTED (CSV)
========================= */
if (isset($_POST['download_selected'])) {
    if (!empty($_POST['selected'])) {
        $ids = implode(',', array_map('intval', $_POST['selected']));
        $res = mysqli_query($conn, "SELECT * FROM students WHERE id IN ($ids)");
        ob_clean();
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=selected_students.csv");
        echo "ID,Name,Email,Phone,Department,Domain,Photos\n";
        while ($r = mysqli_fetch_assoc($res)) {
            $photos = json_decode($r['photo'], true) ?: [];
            $photos_str = implode(';', $photos);
            $line = [
                $r['id'],
                '"' . str_replace('"', '""', $r['name']) . '"',
                '"' . str_replace('"', '""', $r['email']) . '"',
                '"' . str_replace('"', '""', $r['phone']) . '"',
                '"' . str_replace('"', '""', $r['department']) . '"',
                '"' . str_replace('"', '""', $r['domain']) . '"',
                '"' . str_replace('"', '""', $photos_str) . '"'
            ];
            echo implode(',', $line) . "\n";
        }
        exit;
    } else {
        $message = "❌ No rows selected!";
        $showTable = true;
    }
}

/* =========================
   DELETE SINGLE
========================= */
if (isset($_POST['delete_single'])) {
    $id = intval($_POST['delete_single']);
    $qr = mysqli_query($conn, "SELECT photo FROM students WHERE id=$id");
    if ($qr && $r = mysqli_fetch_assoc($qr)) {
        $photos = json_decode($r['photo'], true) ?: [];
        foreach ($photos as $f) {
            $p = __DIR__ . "/uploads/" . $f;
            if (file_exists($p)) @unlink($p);
        }
    }
    mysqli_query($conn, "DELETE FROM students WHERE id=$id");
    $message = "✔ Row deleted!";
    $showTable = true;
}

/* =========================
   DELETE SELECTED
========================= */
if (isset($_POST['delete_selected'])) {
    if (!empty($_POST['selected'])) {
        $ids_arr = array_map('intval', $_POST['selected']);
        $ids = implode(',', $ids_arr);
        $qr = mysqli_query($conn, "SELECT photo FROM students WHERE id IN ($ids)");
        while ($r = mysqli_fetch_assoc($qr)) {
            $photos = json_decode($r['photo'], true) ?: [];
            foreach ($photos as $f) {
                $p = __DIR__ . "/uploads/" . $f;
                if (file_exists($p)) @unlink($p);
            }
        }
        mysqli_query($conn, "DELETE FROM students WHERE id IN ($ids)");
        $message = "✔ Selected rows deleted!";
        $showTable = true;
    } else {
        $message = "❌ No rows selected!";
        $showTable = true;
    }
}

/* =========================
   EDIT LOAD
========================= */
if (isset($_POST['edit_single'])) {
    $id = intval($_POST['edit_single']);
    $res = mysqli_query($conn, "SELECT * FROM students WHERE id=$id");
    if ($res) {
        $editData = mysqli_fetch_assoc($res) ?: [];
        $editMode = !empty($editData);
    }
    $showTable = true;
}

/* =========================
   UPDATE (edit)
========================= */
if (isset($_POST['update_data'])) {

    if (empty($_POST['edit_id'])) {
        $message = "❌ Update failed: Missing edit ID";
        $showTable = true;
        $editMode = false;
    }

    $id = intval($_POST['edit_id']);

    $name   = mysqli_real_escape_string($conn, $_POST['name']);
    $email  = mysqli_real_escape_string($conn, $_POST['email']);
    $phone  = mysqli_real_escape_string($conn, $_POST['phone']);
    $dept   = mysqli_real_escape_string($conn, $_POST['department']);
    $domain = mysqli_real_escape_string($conn, $_POST['domain']);

    // existing photos always decode safely
    $existing_photos_json = $_POST['existing_photos'] ?? '[]';
    $existing_photos = json_decode(stripslashes($existing_photos_json), true);
    if (!is_array($existing_photos)) $existing_photos = [];

    $new_uploaded = [];

    // file upload
    if (!empty($_FILES['photos']['name'][0])) {

        for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {

            if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {

                $orig = basename($_FILES['photos']['name'][$i]);
                $safe = uniqid() . "_" . clean_filename($orig);
                $dest = __DIR__ . "/uploads/" . $safe;

                if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $dest)) {
                    $new_uploaded[] = $safe;
                }
            }
        }

        // delete old only if new uploaded
        foreach ($existing_photos as $old) {
            $path = __DIR__ . "/uploads/" . $old;
            if (file_exists($path)) unlink($path);
        }

        $final_photos = $new_uploaded;

    } else {
        // keep old
        $final_photos = $existing_photos;
    }

    $photos_json = mysqli_real_escape_string($conn, json_encode($final_photos));

    $sql = "
    UPDATE students SET 
        name='$name',
        email='$email',
        phone='$phone',
        department='$dept',
        domain='$domain',
        photo='$photos_json'
    WHERE id=$id
    ";

    if (mysqli_query($conn, $sql)) {
        $message = "✔ Student updated successfully!";
        $editMode = false;
        $showTable = true;
    } else {
        $message = "❌ Update error: " . mysqli_error($conn);
        $showTable = true;
    }
}



/* =========================
   PAGINATION & SHOW TABLE DECISION
========================= */
if (isset($_GET['page'])) {
    $page_req = intval($_GET['page']);
    if ($page_req < 1) $page_req = 1;
    $showTable = true;
}

// Show table for filter_dates as well
if ((isset($_GET['filter_date']) && $_GET['filter_date'] !== "") || (isset($_GET['filter_dates']) && $_GET['filter_dates'] !== "")) {
    $showTable = true;
}

if (isset($_GET['show_all'])) {
    $showTable = true;
}

/* =========================
   HTML Output (starts below)
========================= */
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Student Registration</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
/* small inline override for calendar z-index only */
.calendar-popup .flatpickr-calendar { z-index: 99999; }
</style>
</head>
<body>

<h2 class="ten">Student Registration</h2>

<?php $displayForm = true; ?>
<?php if ($displayForm): ?>
<form id="editForm" method="post" class="student-form" enctype="multipart/form-data">
    <input type="hidden" name="edit_id" value="<?= $editMode ? htmlspecialchars($editData['id']) : '' ?>">
    <label>Name:</label>
    <input type="text" name="name" required value="<?= $editMode ? htmlspecialchars($editData['name']) : '' ?>">
    <label>Email:</label>
    <input type="email" name="email" required value="<?= $editMode ? htmlspecialchars($editData['email']) : '' ?>">
    <label>Phone:</label>
    <input type="text" name="phone" required value="<?= $editMode ? htmlspecialchars($editData['phone']) : '' ?>">
    <label>Department:</label>
    <input type="text" name="department" required value="<?= $editMode ? htmlspecialchars($editData['department']) : '' ?>">
    <label>Domain:</label>
    <select name="domain" required>
        <option value="">-- Select --</option>
        <option value="python" <?= ($editMode && $editData['domain']=="python")?"selected":"" ?>>Python</option>
        <option value="java" <?= ($editMode && $editData['domain']=="java")?"selected":"" ?>>Java</option>
        <option value="php" <?= ($editMode && $editData['domain']=="php")?"selected":"" ?>>PHP</option>
    </select>

    <label>Photos (1–5):</label>
    <input type="file" name="photos[]" accept="image/*" multiple onchange="limitFiles(this)">
    <?php if ($editMode):
        $existing = json_decode($editData['photo'], true) ?: [];
    ?>
        <div class="small-note">Existing Photos:</div>
        <div class="existing-photos">
        <?php foreach ($existing as $ex):
            $path = __DIR__ . "/uploads/" . $ex;
            $webpath = "uploads/" . $ex;
            if (!file_exists($path)) {
                // if file missing, show a placeholder (do not break page)
                echo '<div class="missing-thumb">(missing)</div>';
            } else {
                echo '<img src="' . htmlspecialchars($webpath) . '" class="table-thumb" alt="">';
            }
        endforeach; ?>
        </div>
        <input type="hidden" name="existing_photos" value='<?= htmlspecialchars(json_encode($existing)) ?>'>
    <?php endif; ?>

    <?php if ($editMode): ?>
        <button type="submit" name="update_data" class="update-btn">Update</button>
    <?php else: ?>
        <button type="submit" name="save_data" class="first">Save</button>
    <?php endif; ?>
</form>
<?php endif; ?>

<?php if ($message): ?>
<div class="message-centered"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- TABLE / FILTER AREA -->
<?php
if ($showTable):
    $limit = 10;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $start = ($page - 1) * $limit;

    // DATE FILTER (BETWEEN) + preload
    $where = "";
    $filter_param = "";
    $active_start = "";
    $active_end = "";
    if (isset($_GET['filter_dates']) && $_GET['filter_dates'] !== "") {
        $parts = explode(",", $_GET['filter_dates']);
        if (count($parts) >= 2) {
            $s = trim($parts[0]);
            $e = trim($parts[1]);
            if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $s) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $e)) {
                $s_safe = mysqli_real_escape_string($conn, $s);
                $e_safe = mysqli_real_escape_string($conn, $e);
                $where = "WHERE DATE(created_at) BETWEEN '$s_safe' AND '$e_safe'";
                $filter_param = "filter_dates={$s_safe},{$e_safe}&";
                $active_start = $s_safe;
                $active_end = $e_safe;
            }
        }
    }

    // count & fetch
    $totalRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM students $where");
    $totalRow = mysqli_fetch_assoc($totalRes);
    $totalRecords = intval($totalRow['total']);
    $totalPages = max(1, ceil($totalRecords / $limit));
    if ($page > $totalPages) $page = $totalPages;
    $start = ($page - 1) * $limit;

    $res = mysqli_query($conn, "SELECT * FROM students $where ORDER BY id DESC LIMIT $start, $limit");
?>
    <h3 class="two" id="students_table">
        <?= (!empty($active_start) && !empty($active_end)) ? "Showing Records from " . htmlspecialchars($active_start) . " to " . htmlspecialchars($active_end) : "All Registered Students" ?>
    </h3>

    <form method="post">
        <div class="action-buttons-container">
            <button type="submit" name="download_selected" class="download-selected">Download Selected</button>

            <!-- Calendar icon-only + popup (inline calendar + buttons inside) -->
            <div class="calendar-filter-box" style="position:relative;">
                <button type="button" id="calendarIcon" class="calendar-icon" aria-expanded="false" aria-controls="calendarPopup" title="Select date range">📅</button>

                <!-- hidden to carry selected dates for preloading & server-side checks -->
                <input type="hidden" id="selectedDates" name="filter_dates" value="<?= !empty($active_start) && !empty($active_end) ? htmlspecialchars($active_start . "," . $active_end) : '' ?>">

                <div id="calendarPopup" class="calendar-popup" aria-hidden="true">
                    <div id="calendarArea"></div>
                    <div class="popup-buttons">
                        <button id="applyFilter" type="button">Filter</button>
                        <button id="resetFilter" type="button">Reset</button>
                    </div>
                </div>
            </div>

            <button type="submit" name="delete_selected" class="delete-selected">Delete Selected</button>
        </div>

        <table class="data-table" id="studentTable">
            <thead>
                <tr>
                    <th>Select</th>
                    <th>ID<span id="sortArrow" style="cursor:pointer; margin-left:5px; font-size:14px;" onclick="sortTable()">▲</span>
</th>

                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Department</th>
                    <th>Domain</th>
                    <th>Photos</th>
                    <th>View</th><th>Edit</th><th>Delete</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($res)):
                $photos = json_decode($row['photo'], true) ?: [];
            ?>
                <tr>
                    <td><input type="checkbox" name="selected[]" value="<?= intval($row['id']) ?>"></td>
                    <td><?= intval($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['domain']) ?></td>
                    <td>
                        <?php if ($photos): foreach ($photos as $p):
                            $pth = __DIR__ . "/uploads/" . $p;
                            if (!file_exists($pth)) {
                                echo '<span style="color:#c33">(missing)</span>';
                            } else {
                                echo '<img src="uploads/' . htmlspecialchars($p) . '" class="table-thumb" alt="">';
                            }
                        endforeach; else: ?>
                            <span style="color:#666;">(none)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="view-btn"
                            onclick='showDetails(<?= json_encode(htmlspecialchars($row["name"])) ?>,
                                                  <?= json_encode(htmlspecialchars($row["email"])) ?>,
                                                  <?= json_encode(htmlspecialchars($row["phone"])) ?>,
                                                  <?= json_encode(htmlspecialchars($row["department"])) ?>,
                                                  <?= json_encode(htmlspecialchars($row["domain"])) ?>,
                                                  <?= json_encode($photos) ?>)'>View</button>
                    </td>
                    <td><button name="edit_single" value="<?= intval($row['id']) ?>" class="edit-btn">Edit</button></td>
                    <td><button name="delete_single" value="<?= intval($row['id']) ?>" class="delete-btn">Delete</button></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </form>

    <!-- pagination -->
    <div class="pagination">
        <?php
        $base = "?";
        if (!empty($filter_param)) $base .= $filter_param;
        if ($page > 1) {
            $prev = $page - 1;
            echo "<a class='btn' href='{$base}page={$prev}#students_table' onclick='scrollToTable();'>Previous</a>";
        }
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $page) ? "active" : "";
            echo "<a class='btn {$active}' href='{$base}page={$i}#students_table' onclick='scrollToTable();'>{$i}</a>";
        }
        if ($page < $totalPages) {
            $next = $page + 1;
            echo "<a class='btn' href='{$base}page={$next}#students_table' onclick='scrollToTable();'>Next</a>";
        }
        ?>
    </div>
<?php endif; ?>

<!-- View popup -->
<div id="popup" class="popup">
    <div class="popup-content">
        <span class="close-btn" onclick="closePopup()">×</span>
        <h3>Student Details</h3>
        <p><strong>Name:</strong> <span id="p_name"></span></p>
        <p><strong>Email:</strong> <span id="p_email"></span></p>
        <p><strong>Phone:</strong> <span id="p_phone"></span></p>
        <p><strong>Department:</strong> <span id="p_dept"></span></p>
        <p><strong>Domain:</strong> <span id="p_domain"></span></p>
        <div id="p_photos" class="photos-list"></div>
    </div>
</div>

<!-- SCRIPTS -->
<script>
/* helper functions for UI */
function limitFiles(input) {
    if (input.files.length > 5) {
        alert("You can upload a maximum of 5 photos.");
        input.value = "";
    }
}
function showDetails(name, email, phone, dept, domain, photos) {
    document.getElementById('p_name').innerText = name;
    document.getElementById('p_email').innerText = email;
    document.getElementById('p_phone').innerText = phone;
    document.getElementById('p_dept').innerText = dept;
    document.getElementById('p_domain').innerText = domain;
    var container = document.getElementById('p_photos');
    container.innerHTML = '';
    if (Array.isArray(photos) && photos.length) {
        photos.forEach(function(p){
            var img = document.createElement('img');
            img.src = "uploads/" + p;
            img.className = "popup-img";
            container.appendChild(img);
        });
    } else {
        container.textContent = '(no photos)';
    }
    document.getElementById('popup').style.display = 'flex';
}
function closePopup() { document.getElementById('popup').style.display = 'none'; }
function scrollToTable() {
    var el = document.getElementById("studentTable");
    if (!el) return;
    el.scrollIntoView({ behavior: "smooth", block: "start" });
}
</script>

<script>
// ---------- Calendar & popup logic (clean, single copy) ----------
const calendarIcon = document.getElementById('calendarIcon');
const calendarPopup = document.getElementById('calendarPopup');
const selectedDatesInput = document.getElementById('selectedDates');
const applyFilterBtn = document.getElementById('applyFilter');
const resetFilterBtn = document.getElementById('resetFilter');
const calendarArea = document.getElementById('calendarArea');

let startDate = null, endDate = null;

const fp = flatpickr("#calendarArea", {
    mode: "range",
    dateFormat: "Y-m-d",
    inline: true,
    onChange: function(selectedDates) {
        if (selectedDates.length === 1) {
            startDate = fp.formatDate(selectedDates[0], "Y-m-d");
            endDate = startDate;
        } else if (selectedDates.length === 2) {
            startDate = fp.formatDate(selectedDates[0], "Y-m-d");
            endDate = fp.formatDate(selectedDates[1], "Y-m-d");
        } else {
            startDate = null;
            endDate = null;
        }
        // keep hidden input in sync (useful when preloading)
        selectedDatesInput.value = startDate && endDate ? (startDate + ',' + endDate) : '';
    }
});

// Preload server-side filter into fp if present
(function preloadDates() {
    try {
        const val = selectedDatesInput.value;
        if (!val) return;
        const parts = val.split(',');
        if (parts.length >= 2 && parts[0] && parts[1]) {
            fp.setDate([parts[0], parts[1]], true, "Y-m-d");
            startDate = parts[0];
            endDate = parts[1];
        }
    } catch (e) { /* ignore preload errors */ }
})();

// Append popup to body so it is not clipped by containers
if (calendarPopup && calendarPopup.parentElement !== document.body) {
    document.body.appendChild(calendarPopup);
}
function measurePopup(popup) {
    popup.style.visibility = 'hidden';
    popup.style.display = 'block';
    const w = popup.offsetWidth;
    const h = popup.offsetHeight;
    popup.style.display = 'none';
    popup.style.visibility = '';
    return { w, h };
}
function openCalendarPopup() {
    const rect = calendarIcon.getBoundingClientRect();
    const dims = measurePopup(calendarPopup);
    let left = rect.left;
    let top = rect.bottom + 8;
    const pad = 8;
    const maxRight = window.innerWidth - pad;
    if (left + dims.w > maxRight) left = Math.max(pad, maxRight - dims.w);
    if (left < pad) left = pad;
    const maxBottom = window.innerHeight - pad;
    if (top + dims.h > maxBottom) {
        const altTop = rect.top - dims.h - 8;
        if (altTop >= pad) top = altTop;
        else top = pad;
    }
    if (top < pad) top = pad;
    calendarPopup.style.left = Math.round(left) + 'px';
    calendarPopup.style.top  = Math.round(top) + 'px';
    calendarPopup.style.position = 'fixed';
    calendarPopup.style.display = 'block';
    calendarPopup.setAttribute('aria-hidden', 'false');
    calendarIcon.setAttribute('aria-expanded', 'true');
    try { fp.redraw && fp.redraw(); } catch (e) {}
}
function closeCalendarPopup() {
    calendarPopup.style.display = 'none';
    calendarPopup.setAttribute('aria-hidden', 'true');
    calendarIcon.setAttribute('aria-expanded', 'false');
}

// toggle
calendarIcon.addEventListener('click', function(e) {
    e.stopPropagation();
    if (calendarPopup.style.display === 'block') closeCalendarPopup();
    else openCalendarPopup();
});

// outside click / Esc
document.addEventListener('click', function(ev) {
    if (!calendarPopup.contains(ev.target) && !calendarIcon.contains(ev.target)) {
        if (calendarPopup.style.display === 'block') closeCalendarPopup();
    }
});
document.addEventListener('keydown', function(ev) {
    if (ev.key === 'Escape' && calendarPopup.style.display === 'block') closeCalendarPopup();
});
window.addEventListener('resize', function() { if (calendarPopup.style.display === 'block') openCalendarPopup(); });
window.addEventListener('scroll', function() { if (calendarPopup.style.display === 'block') openCalendarPopup(); });

// Apply filter
applyFilterBtn.addEventListener('click', function(e) {
    e.preventDefault();
    if (!startDate || !endDate) {
        alert('Please select start and end dates (or a single date) before filtering.');
        return;
    }
    window.location.href = "?filter_dates=" + encodeURIComponent(startDate + "," + endDate) + "#students_table";
});

// Reset
resetFilterBtn.addEventListener('click', function(e) {
    e.preventDefault();
    fp.clear();
    selectedDatesInput.value = "";
    startDate = null; endDate = null;
    window.location.href = "?show_all=1#students_table";
});

// Auto-scroll to form when editMode is true (server side sets $editMode)
// We need to ask PHP whether editMode is true; embed flag:
<?php if ($editMode): ?>
window.addEventListener('load', function() {
    const f = document.getElementById('editForm');
    if (f) {
        f.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // focus first input after a short delay
        setTimeout(() => {
            const first = f.querySelector('input, select, textarea');
            if (first) first.focus();
        }, 350);
    }
});
<?php endif; ?>

</script>
<script>

let currentSort = "asc";

function sortTable() {
    currentSort = currentSort === "asc" ? "desc" : "asc";
    document.getElementById("sortArrow").innerHTML = currentSort === "asc" ? "▲" : "▼";

    // send AJAX request to server
    fetch("<?= $_SERVER['PHP_SELF'] ?>?ajax_sort=id&order=" + currentSort)
        .then(res => res.text())
        .then(data => {
            document.querySelector("#studentTable tbody").innerHTML = data;
        });
}



</script>


</body>
</html>
