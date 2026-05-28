<?php
function clean_input(string $value): string
{
    return trim($value);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $value): string
{
    $value = mb_strtolower($value, 'UTF-8');
    $value = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value);
    $value = trim($value, '-');

    if ($value === '') {
        return 'entrada';
    }

    return $value;
}

function ensure_directory(string $path): bool
{
    if (is_dir($path)) {
        return true;
    }

    return mkdir($path, 0775, true);
}

function load_entries(string $jsonFilePath): array
{
    if (!file_exists($jsonFilePath)) {
        return [];
    }

    $jsonContent = file_get_contents($jsonFilePath);

    if ($jsonContent === false) {
        return [];
    }

    $decodedEntries = json_decode($jsonContent, true);

    if (!is_array($decodedEntries)) {
        return [];
    }

    return $decodedEntries;
}

function sort_entries_alphabetically(array &$entries): void
{
    usort($entries, function (array $a, array $b): int {
        $nameA = mb_strtolower((string) ($a['name'] ?? ''), 'UTF-8');
        $nameB = mb_strtolower((string) ($b['name'] ?? ''), 'UTF-8');

        return strcmp($nameA, $nameB);
    });
}

function find_entry_by_id(array $entries, string $entryId): ?array
{
    foreach ($entries as $entry) {
        if ((string) ($entry['id'] ?? '') === $entryId) {
            return $entry;
        }
    }

    return null;
}

function map_entry_to_form_data(array $entry): array
{
    $mappedAttributes = $entry['attributes'] ?? [];

    if (!is_array($mappedAttributes) || empty($mappedAttributes)) {
        $mappedAttributes = [
            ['name' => '', 'value' => ''],
        ];
    }

    return [
        'id' => (string) ($entry['id'] ?? ''),
        'entry_name' => (string) ($entry['name'] ?? ''),
        'entry_type' => (string) ($entry['type'] ?? 'ciudad'),
        'entry_description' => (string) ($entry['description'] ?? ''),
        'entry_notes' => (string) ($entry['notes'] ?? ''),
        'image_path' => (string) ($entry['image_path'] ?? ''),
        'created_at' => (string) ($entry['created_at'] ?? ''),
        'updated_at' => (string) ($entry['updated_at'] ?? ''),
        'attributes' => $mappedAttributes,
    ];
}

$pageTitle = 'Porcelain Codex';
$currentMode = 'cartografo';

$allowedTypes = ['ciudad', 'mazmorra', 'ruina', 'bosque'];
$allowedImageMimeTypes = [
    'image/jpeg' => 'jpg',
'image/png' => 'png',
'image/webp' => 'webp',
];
$maxImageSize = 2 * 1024 * 1024;

$errors = [];
$successMessage = '';
$storedEntries = [];

$projectRoot = __DIR__;
$dataDirectory = $projectRoot . '/data';
$uploadsDirectory = $projectRoot . '/assets/img/uploads';
$jsonFilePath = $dataDirectory . '/entries.json';

$selectedEntryId = clean_input($_GET['entry'] ?? '');
$isCreatingNew = isset($_GET['new']) && $_GET['new'] === '1';
$currentViewMode = clean_input($_GET['mode'] ?? 'view');

if (!in_array($currentViewMode, ['view', 'edit'], true)) {
    $currentViewMode = 'view';
}

$formData = [
    'id' => '',
'entry_name' => '',
'entry_type' => 'ciudad',
'entry_description' => '',
'entry_notes' => '',
'image_path' => '',
'created_at' => '',
'updated_at' => '',
'attributes' => [
    [
        'name' => '',
'value' => '',
    ],
[
    'name' => '',
'value' => '',
],
],
];

$storedEntries = load_entries($jsonFilePath);
sort_entries_alphabetically($storedEntries);

if (isset($_GET['ajax']) && $_GET['ajax'] === 'entry') {
    header('Content-Type: application/json; charset=UTF-8');

    $ajaxEntryId = clean_input($_GET['entry'] ?? '');
    $ajaxEntry = find_entry_by_id($storedEntries, $ajaxEntryId);

    if ($ajaxEntry === null) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Entrada no encontrada.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode([
        'success' => true,
        'entry' => $ajaxEntry,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

if ($isCreatingNew) {
    $currentViewMode = 'edit';
}

if (!$isCreatingNew && $selectedEntryId !== '') {
    $selectedEntry = find_entry_by_id($storedEntries, $selectedEntryId);

    if ($selectedEntry !== null) {
        $formData = map_entry_to_form_data($selectedEntry);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['id'] = clean_input($_POST['entry_id'] ?? '');
    $formData['entry_name'] = clean_input($_POST['entry_name'] ?? '');
    $formData['entry_type'] = clean_input($_POST['entry_type'] ?? '');
    $formData['entry_description'] = clean_input($_POST['entry_description'] ?? '');
    $formData['entry_notes'] = clean_input($_POST['entry_notes'] ?? '');
    $formData['image_path'] = clean_input($_POST['existing_image_path'] ?? '');
    $formData['created_at'] = clean_input($_POST['existing_created_at'] ?? '');
    $formData['updated_at'] = clean_input($_POST['existing_updated_at'] ?? '');

    $submittedAttributes = $_POST['attributes'] ?? [];
    $formData['attributes'] = [];

    if ($formData['entry_name'] === '') {
        $errors['entry_name'] = 'Debes escribir un nombre.';
    } elseif (mb_strlen($formData['entry_name']) < 3) {
        $errors['entry_name'] = 'El nombre debe tener al menos 3 caracteres.';
    }

    if (!in_array($formData['entry_type'], $allowedTypes, true)) {
        $errors['entry_type'] = 'Debes seleccionar un tipo válido.';
    }

    if ($formData['entry_description'] === '') {
        $errors['entry_description'] = 'Debes escribir una descripción general.';
    } elseif (mb_strlen($formData['entry_description']) < 10) {
        $errors['entry_description'] = 'La descripción debe tener al menos 10 caracteres.';
    }

    if ($formData['entry_notes'] !== '' && mb_strlen($formData['entry_notes']) < 5) {
        $errors['entry_notes'] = 'Si escribes notas, deben tener al menos 5 caracteres.';
    }

    if (is_array($submittedAttributes)) {
        foreach ($submittedAttributes as $index => $attribute) {
            $attributeName = clean_input($attribute['name'] ?? '');
            $attributeValue = clean_input($attribute['value'] ?? '');

            $formData['attributes'][] = [
                'name' => $attributeName,
                'value' => $attributeValue,
            ];

            if ($attributeName === '' && $attributeValue === '') {
                continue;
            }

            if ($attributeName === '') {
                $errors["attributes_$index" . '_name'] = 'Este tipo de dato necesita un nombre.';
            }

            if ($attributeValue === '') {
                $errors["attributes_$index" . '_value'] = 'Este tipo de dato necesita contenido.';
            }
        }
    }

    if (empty($formData['attributes'])) {
        $formData['attributes'][] = ['name' => '', 'value' => ''];
    }

    $uploadedImage = $_FILES['entry_image'] ?? null;
    $pendingImageData = null;

    if ($uploadedImage !== null && $uploadedImage['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($uploadedImage['error'] !== UPLOAD_ERR_OK) {
            $errors['entry_image'] = 'Hubo un problema al subir la imagen.';
        } elseif ($uploadedImage['size'] > $maxImageSize) {
            $errors['entry_image'] = 'La imagen no puede pesar más de 2 MB.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo === false) {
                $errors['entry_image'] = 'No se pudo validar el tipo real de la imagen.';
            } else {
                $detectedMimeType = finfo_file($finfo, $uploadedImage['tmp_name']);
                finfo_close($finfo);

                if ($detectedMimeType === false || !isset($allowedImageMimeTypes[$detectedMimeType])) {
                    $errors['entry_image'] = 'Solo se permiten imágenes JPG, PNG o WEBP.';
                } else {
                    $pendingImageData = [
                        'tmp_name' => $uploadedImage['tmp_name'],
                        'extension' => $allowedImageMimeTypes[$detectedMimeType],
                    ];
                }
            }
        }
    }

    if (empty($errors)) {
        if (!ensure_directory($dataDirectory)) {
            $errors['storage'] = 'No se pudo crear la carpeta de datos.';
        }

        if (!ensure_directory($uploadsDirectory)) {
            $errors['storage'] = 'No se pudo crear la carpeta de imágenes.';
        }
    }

    if (empty($errors) && $pendingImageData !== null) {
        $safeFileName = slugify($formData['entry_name']) . '-' . time() . '.' . $pendingImageData['extension'];
        $destinationPath = $uploadsDirectory . '/' . $safeFileName;

        if (!move_uploaded_file($pendingImageData['tmp_name'], $destinationPath)) {
            $errors['entry_image'] = 'No se pudo mover la imagen subida.';
        } else {
            $formData['image_path'] = 'assets/img/uploads/' . $safeFileName;
        }
    }

    if (empty($errors)) {
        $existingEntries = load_entries($jsonFilePath);
        $cleanAttributes = [];

        foreach ($formData['attributes'] as $attribute) {
            if ($attribute['name'] === '' && $attribute['value'] === '') {
                continue;
            }

            $cleanAttributes[] = [
                'name' => $attribute['name'],
                'value' => $attribute['value'],
            ];
        }

        $isUpdatingExistingEntry = $formData['id'] !== '';
        $entryWasUpdated = false;
        $currentTimestamp = date('c');

        if ($isUpdatingExistingEntry) {
            foreach ($existingEntries as $index => $entry) {
                if ((string) ($entry['id'] ?? '') === $formData['id']) {
                    $existingEntries[$index] = [
                        'id' => $formData['id'],
                        'name' => $formData['entry_name'],
                        'type' => $formData['entry_type'],
                        'description' => $formData['entry_description'],
                        'notes' => $formData['entry_notes'],
                        'image_path' => $formData['image_path'],
                        'attributes' => $cleanAttributes,
                        'created_at' => $entry['created_at'] ?? $currentTimestamp,
                        'updated_at' => $currentTimestamp,
                    ];

                    $entryWasUpdated = true;
                    break;
                }
            }
        }

        if (!$entryWasUpdated) {
            $newEntry = [
                'id' => uniqid('entry_', true),
                'name' => $formData['entry_name'],
                'type' => $formData['entry_type'],
                'description' => $formData['entry_description'],
                'notes' => $formData['entry_notes'],
                'image_path' => $formData['image_path'],
                'attributes' => $cleanAttributes,
                'created_at' => $currentTimestamp,
                'updated_at' => '',
            ];

            $existingEntries[] = $newEntry;
            $formData['id'] = $newEntry['id'];
            $formData['created_at'] = $newEntry['created_at'];
            $formData['updated_at'] = $newEntry['updated_at'];
        } else {
            $formData['updated_at'] = $currentTimestamp;
        }

        $encodedJson = json_encode(
            $existingEntries,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($encodedJson === false) {
            $errors['storage'] = 'No se pudieron convertir los datos a JSON.';
        } else {
            $writeResult = file_put_contents($jsonFilePath, $encodedJson);

            if ($writeResult === false) {
                $errors['storage'] = 'No se pudo guardar el archivo JSON.';
            } else {
                $storedEntries = load_entries($jsonFilePath);
                sort_entries_alphabetically($storedEntries);

                $savedEntry = find_entry_by_id($storedEntries, $formData['id']);

                if ($savedEntry !== null) {
                    $formData = map_entry_to_form_data($savedEntry);
                    $selectedEntryId = $savedEntry['id'];
                }

                $successMessage = $entryWasUpdated
                ? 'Entrada actualizada correctamente.'
                : 'Entrada creada correctamente.';

                $currentViewMode = 'edit';
            }
        }
    }
}

$selectedEntryForView = null;

if ($selectedEntryId !== '') {
    $selectedEntryForView = find_entry_by_id($storedEntries, $selectedEntryId);
} elseif ($formData['id'] !== '') {
    $selectedEntryForView = [
        'id' => $formData['id'],
        'name' => $formData['entry_name'],
        'type' => $formData['entry_type'],
        'description' => $formData['entry_description'],
        'notes' => $formData['entry_notes'],
        'image_path' => $formData['image_path'],
        'attributes' => $formData['attributes'],
        'created_at' => $formData['created_at'],
        'updated_at' => $formData['updated_at'],
    ];
}

$isReadOnlyMode = $currentViewMode === 'view';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/partials/header.php'; ?>

<main class="page">
<section class="map-section">
<div class="mode-switch" aria-label="Modo principal">
<button class="mode-button mode-button--active" type="button">Cartógrafo</button>
<button class="mode-button" type="button">Bestiario</button>
</div>

<div class="map-card">
<div class="map-placeholder">
<span>Mapa de la región</span>
</div>
</div>

<div class="nearby-card">
<h2 class="section-title">Lugares cercanos</h2>

<div class="nearby-scroll">
<div class="nearby-list">
<article class="nearby-item">
<h3>Ciudad Pantano</h3>
<p>Ruinas antiguas y canales oscuros.</p>
</article>

<article class="nearby-item">
<h3>Gruta de Nacre</h3>
<p>Entrada estrecha, humedad y ecos.</p>
</article>

<article class="nearby-item">
<h3>Torre Hundida</h3>
<p>Restos de vigilancia costera.</p>
</article>

<article class="nearby-item">
<h3>Astillero Roto</h3>
<p>Barcos quebrados y contrabandistas.</p>
</article>

<article class="nearby-item">
<h3>Faro de Marfil</h3>
<p>Luz arcana que nunca se apaga.</p>
</article>

<article class="nearby-item">
<h3>Cruce del Peregrino</h3>
<p>Campamento usado por viajeros.</p>
</article>

<article class="nearby-item">
<h3>Templo de Sal</h3>
<p>Santuario viejo, erosionado y blanco.</p>
</article>

<article class="nearby-item">
<h3>Valle de los Ecos</h3>
<p>Voces repetidas entre rocas húmedas.</p>
</article>

<article class="nearby-item">
<h3>Sueños de porcelana</h3>
<p>El barco ha cambiado, pero sigue siendo tu hogar.</p>
</article>

<article class="nearby-item">
<h3>Corazones de terciopelo</h3>
<p>Sientes una extraña sensación de calidez y hogar.</p>
</article>
</div>
</div>
</div>
</section>

<section class="content-grid" id="editor-section">
<div class="main-panel">
<form
class="entry-form"
action="#editor-section"
method="post"
enctype="multipart/form-data"
novalidate
data-view-mode="<?= e($currentViewMode) ?>"
>
<div class="panel-toolbar">
<div class="panel-actions-left">
<a href="?new=1&mode=edit#editor-section" class="toolbar-button">Agregar nuevo</a>
</div>

<div class="panel-actions-right">
<?php if ($formData['id'] !== ''): ?>
<a
href="?entry=<?= urlencode($formData['id']) ?>&mode=view#editor-section"
class="toolbar-button <?= $currentViewMode === 'view' ? 'mode-button--active' : '' ?>"
id="view-mode-button"
>Ver</a>

<a
href="?entry=<?= urlencode($formData['id']) ?>&mode=edit#editor-section"
class="toolbar-button <?= $currentViewMode === 'edit' ? 'mode-button--active' : '' ?>"
id="edit-mode-button"
>Editar</a>
<?php else: ?>
<button type="button" class="toolbar-button mode-button--active">Editar</button>
<?php endif; ?>

<?php if ($currentViewMode === 'edit'): ?>
<button type="submit" class="toolbar-button toolbar-button--primary">Guardar</button>
<?php endif; ?>
</div>
</div>

<?php if (!empty($errors)): ?>
<div class="form-message form-message--error">
<p><strong>Hay errores en el formulario.</strong> Revisa los campos marcados abajo.</p>
</div>
<?php endif; ?>

<?php if (isset($errors['storage'])): ?>
<div class="form-message form-message--error">
<p><?= e($errors['storage']) ?></p>
</div>
<?php endif; ?>

<?php if ($successMessage !== ''): ?>
<div class="form-message form-message--success">
<p><?= e($successMessage) ?></p>
</div>
<?php endif; ?>

<div class="entry-form-layout">
<div class="entry-form-main">
<input type="hidden" name="entry_id" value="<?= e($formData['id']) ?>">
<input type="hidden" name="existing_image_path" value="<?= e($formData['image_path']) ?>">
<input type="hidden" name="existing_created_at" value="<?= e($formData['created_at']) ?>">
<input type="hidden" name="existing_updated_at" value="<?= e($formData['updated_at']) ?>">

<?php if ($currentViewMode === 'view'): ?>
<section
class="entry-visual-panel entry-visual-panel--main"
id="entry-visual-panel"
>
<?php if ($selectedEntryForView !== null): ?>
<div class="entry-visual-panel__media">
<?php if (($selectedEntryForView['image_path'] ?? '') !== ''): ?>
<img
src="<?= e((string) $selectedEntryForView['image_path']) ?>"
alt="Imagen de <?= e((string) ($selectedEntryForView['name'] ?? 'la entrada')) ?>"
class="entry-visual-image"
>
<?php else: ?>
<div class="entry-visual-placeholder">Sin imagen</div>
<?php endif; ?>
</div>

<div class="entry-visual-panel__content">
<p class="entry-visual-kicker"><?= e(ucfirst((string) ($selectedEntryForView['type'] ?? 'lugar'))) ?></p>
<h2 class="entry-visual-title"><?= e((string) ($selectedEntryForView['name'] ?? 'Sin nombre')) ?></h2>
<p class="entry-visual-description"><?= e((string) ($selectedEntryForView['description'] ?? '')) ?></p>

<?php if (!empty($selectedEntryForView['attributes']) && is_array($selectedEntryForView['attributes'])): ?>
<div class="entry-visual-attributes">
<?php foreach ($selectedEntryForView['attributes'] as $attribute): ?>
<?php
$attributeName = (string) ($attribute['name'] ?? '');
$attributeValue = (string) ($attribute['value'] ?? '');
?>
<?php if ($attributeName !== '' || $attributeValue !== ''): ?>
<article class="entry-visual-attribute">
<h3><?= e($attributeName !== '' ? $attributeName : 'Dato') ?></h3>
<p><?= e($attributeValue) ?></p>
</article>
<?php endif; ?>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ((string) ($selectedEntryForView['notes'] ?? '') !== ''): ?>
<div class="entry-visual-notes">
<h3>Notas</h3>
<p><?= e((string) $selectedEntryForView['notes']) ?></p>
</div>
<?php endif; ?>
</div>
<?php else: ?>
<div class="entry-visual-empty">
<p>Selecciona un lugar de la lista para verlo aquí.</p>
</div>
<?php endif; ?>
</section>

<?php else: ?>
<div class="entry-editor-fields">
<section class="image-upload-card">
<label for="entry-image" class="image-upload-label">
<?php if ($formData['image_path'] !== ''): ?>
<img src="<?= e($formData['image_path']) ?>" alt="Imagen actual de la entrada" class="entry-preview-image">
<?php else: ?>
<span class="image-upload-plus">+</span>
<span class="image-upload-text">Haz clic aquí para subir una imagen</span>
<?php endif; ?>
</label>

<input
id="entry-image"
name="entry_image"
type="file"
accept="image/*"
class="visually-hidden"
>

<?php if (isset($errors['entry_image'])): ?>
<p class="field-error"><?= e($errors['entry_image']) ?></p>
<?php endif; ?>

<?php if ($formData['image_path'] !== ''): ?>
<p class="upload-hint">Imagen actual: <?= e($formData['image_path']) ?></p>
<?php endif; ?>
</section>

<section class="form-card">
<div class="form-field">
<label for="entry-name">Nombre</label>
<input
type="text"
id="entry-name"
name="entry_name"
value="<?= e($formData['entry_name']) ?>"
placeholder="Escribe el nombre del lugar"
>
<?php if (isset($errors['entry_name'])): ?>
<p class="field-error"><?= e($errors['entry_name']) ?></p>
<?php endif; ?>
</div>

<div class="form-field">
<label for="entry-type">Tipo</label>
<select id="entry-type" name="entry_type">
<option value="ciudad" <?= $formData['entry_type'] === 'ciudad' ? 'selected' : '' ?>>Ciudad</option>
<option value="mazmorra" <?= $formData['entry_type'] === 'mazmorra' ? 'selected' : '' ?>>Mazmorra</option>
<option value="ruina" <?= $formData['entry_type'] === 'ruina' ? 'selected' : '' ?>>Ruina</option>
<option value="bosque" <?= $formData['entry_type'] === 'bosque' ? 'selected' : '' ?>>Bosque</option>
</select>
<?php if (isset($errors['entry_type'])): ?>
<p class="field-error"><?= e($errors['entry_type']) ?></p>
<?php endif; ?>
</div>

<div class="form-field">
<label for="entry-description">Descripción general</label>
<textarea
id="entry-description"
name="entry_description"
rows="5"
placeholder="Describe brevemente el lugar o criatura"
><?= e($formData['entry_description']) ?></textarea>
<?php if (isset($errors['entry_description'])): ?>
<p class="field-error"><?= e($errors['entry_description']) ?></p>
<?php endif; ?>
</div>
</section>

<section class="attributes-section">
<?php foreach ($formData['attributes'] as $index => $attribute): ?>
<article class="attribute-card">
<div class="attribute-card__header">
<label for="attr-name-<?= $index ?>">Tipo de dato</label>
<button
type="button"
class="attribute-remove-button"
aria-label="Eliminar este tipo de dato"
>×</button>
</div>

<input
type="text"
id="attr-name-<?= $index ?>"
name="attributes[<?= $index ?>][name]"
value="<?= e((string) ($attribute['name'] ?? '')) ?>"
placeholder="Ejemplo: Población"
>

<?php if (isset($errors["attributes_$index" . '_name'])): ?>
<p class="field-error"><?= e($errors["attributes_$index" . '_name']) ?></p>
<?php endif; ?>

<label for="attr-value-<?= $index ?>" class="attribute-value-label">Contenido</label>
<textarea
id="attr-value-<?= $index ?>"
name="attributes[<?= $index ?>][value]"
rows="3"
placeholder="Escribe el contenido de este dato"
><?= e((string) ($attribute['value'] ?? '')) ?></textarea>

<?php if (isset($errors["attributes_$index" . '_value'])): ?>
<p class="field-error"><?= e($errors["attributes_$index" . '_value']) ?></p>
<?php endif; ?>
</article>
<?php endforeach; ?>

<button
type="button"
class="add-attribute-button"
>+ Agregar tipo de dato</button>
</section>
</div>
<?php endif; ?>
</div>

<aside class="entry-form-sidebar">
<section class="sidebar-card sidebar-list-card">
<h2 class="section-title">Lista de lugares</h2>

<div class="search-box">
<label for="place-search" class="visually-hidden">Buscar lugar</label>
<input
type="search"
id="place-search"
class="search-input"
placeholder="Buscar lugar..."
>
</div>

<ul class="scrollable-list" id="entries-list">
<?php if (empty($storedEntries)): ?>
<li class="empty-list-message">Aún no hay lugares guardados.</li>
<?php else: ?>
<?php foreach ($storedEntries as $entry): ?>
<?php
$entryId = (string) ($entry['id'] ?? '');
$entryName = (string) ($entry['name'] ?? 'Sin nombre');
$isSelected = $entryId === $selectedEntryId;
?>
<li>
<a
href="?entry=<?= urlencode($entryId) ?>&mode=<?= urlencode($currentViewMode) ?>#editor-section"
class="entry-list-link <?= $isSelected ? 'entry-list-link--active' : '' ?>"
data-entry-id="<?= e($entryId) ?>"
>
<?= e($entryName) ?>
</a>
</li>
<?php endforeach; ?>
<?php endif; ?>
</ul>
</section>

<section class="sidebar-card">
<h2 class="section-title">Notas</h2>

<div class="form-field">
<label for="entry-notes">Notas rápidas</label>
<textarea
id="entry-notes"
name="entry_notes"
rows="10"
placeholder="Escribe notas adicionales aquí"
<?= $isReadOnlyMode ? 'readonly' : '' ?>
><?= e($formData['entry_notes']) ?></textarea>
<?php if (isset($errors['entry_notes'])): ?>
<p class="field-error"><?= e($errors['entry_notes']) ?></p>
<?php endif; ?>
</div>
</section>
</aside>
</div>
</form>
</div>
</section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const entryLinks = document.querySelectorAll('.entry-list-link');
    const visualPanel = document.getElementById('entry-visual-panel');
    const entryForm = document.querySelector('.entry-form');
    const entryIdInput = document.querySelector('input[name="entry_id"]');
    const existingImagePathInput = document.querySelector('input[name="existing_image_path"]');
    const existingCreatedAtInput = document.querySelector('input[name="existing_created_at"]');
    const existingUpdatedAtInput = document.querySelector('input[name="existing_updated_at"]');
    const entryNameInput = document.getElementById('entry-name');
    const entryTypeSelect = document.getElementById('entry-type');
    const entryDescriptionTextarea = document.getElementById('entry-description');
    const entryNotesTextarea = document.getElementById('entry-notes');
    const imageUploadLabel = document.querySelector('.image-upload-label');
    const uploadHint = document.querySelector('.upload-hint');
    const attributesSection = document.querySelector('.attributes-section');

    function escapeHtml(value) {
        return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function renderVisualPanel(entry) {
        if (!visualPanel) {
            return;
        }

        const hasImage = entry.image_path && entry.image_path !== '';
const attributes = Array.isArray(entry.attributes) ? entry.attributes : [];

let attributesHtml = '';

attributes.forEach(function (attribute) {
    const attributeName = attribute.name ?? '';
const attributeValue = attribute.value ?? '';

if (attributeName === '' && attributeValue === '') {
    return;
}

attributesHtml += `
<article class="entry-visual-attribute">
<h3>${escapeHtml(attributeName || 'Dato')}</h3>
<p>${escapeHtml(attributeValue)}</p>
</article>
`;
});

const notesHtml = entry.notes
? `
<div class="entry-visual-notes">
<h3>Notas</h3>
<p>${escapeHtml(entry.notes)}</p>
</div>
`
: '';

visualPanel.innerHTML = `
<div class="entry-visual-panel__media">
${
    hasImage
    ? `<img src="${escapeHtml(entry.image_path)}" alt="Imagen de ${escapeHtml(entry.name || 'la entrada')}" class="entry-visual-image">`
    : `<div class="entry-visual-placeholder">Sin imagen</div>`
}
</div>
<div class="entry-visual-panel__content">
<p class="entry-visual-kicker">${escapeHtml(entry.type || 'lugar')}</p>
<h2 class="entry-visual-title">${escapeHtml(entry.name || 'Sin nombre')}</h2>
<p class="entry-visual-description">${escapeHtml(entry.description || '')}</p>
${attributesHtml !== '' ? `<div class="entry-visual-attributes">${attributesHtml}</div>` : ''}
${notesHtml}
</div>
`;
    }

    function renderImagePreview(imagePath) {
        if (!imageUploadLabel) {
            return;
        }

        if (imagePath && imagePath !== '') {
            imageUploadLabel.innerHTML = `
            <img src="${escapeHtml(imagePath)}" alt="Imagen actual de la entrada" class="entry-preview-image">
            `;
        } else {
            imageUploadLabel.innerHTML = `
            <span class="image-upload-plus">+</span>
            <span class="image-upload-text">Haz clic aquí para subir una imagen</span>
            `;
        }

        if (uploadHint) {
            uploadHint.textContent = imagePath && imagePath !== ''
? `Imagen actual: ${imagePath}`
: '';
        }
    }

    function renderAttributes(attributes) {
        if (!attributesSection) {
            return;
        }

        const addButton = attributesSection.querySelector('.add-attribute-button');
        const cards = attributesSection.querySelectorAll('.attribute-card');

        cards.forEach(function (card) {
            card.remove();
        });

        const safeAttributes = Array.isArray(attributes) && attributes.length > 0
        ? attributes
        : [{ name: '', value: '' }];

        const isReadOnlyMode = entryForm?.dataset.viewMode === 'view';

safeAttributes.forEach(function (attribute, index) {
    const card = document.createElement('article');
    card.className = 'attribute-card';

card.innerHTML = `
<div class="attribute-card__header">
<label for="attr-name-${index}">Tipo de dato</label>
<button type="button" class="attribute-remove-button" aria-label="Eliminar este tipo de dato" ${isReadOnlyMode ? 'disabled' : ''}>×</button>
</div>

<input
type="text"
id="attr-name-${index}"
name="attributes[${index}][name]"
value="${escapeHtml(attribute.name ?? '')}"
placeholder="Ejemplo: Población"
${isReadOnlyMode ? 'readonly' : ''}
>

<label for="attr-value-${index}" class="attribute-value-label">Contenido</label>
<textarea
id="attr-value-${index}"
name="attributes[${index}][value]"
rows="3"
placeholder="Escribe el contenido de este dato"
${isReadOnlyMode ? 'readonly' : ''}
>${escapeHtml(attribute.value ?? '')}</textarea>
`;

attributesSection.insertBefore(card, addButton);
});

if (addButton) {
    addButton.disabled = isReadOnlyMode;
}
    }

    function updateActiveLink(selectedId) {
        entryLinks.forEach(function (link) {
            link.classList.toggle('entry-list-link--active', link.dataset.entryId === selectedId);
        });
    }

    async function loadEntry(entryId) {
        const response = await fetch(`?ajax=entry&entry=${encodeURIComponent(entryId)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error('No se pudo cargar la entrada.');
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'No se pudo cargar la entrada.');
        }

        const entry = data.entry;

        if (entryIdInput) entryIdInput.value = entry.id ?? '';
if (existingImagePathInput) existingImagePathInput.value = entry.image_path ?? '';
if (existingCreatedAtInput) existingCreatedAtInput.value = entry.created_at ?? '';
if (existingUpdatedAtInput) existingUpdatedAtInput.value = entry.updated_at ?? '';
if (entryNameInput) entryNameInput.value = entry.name ?? '';
if (entryTypeSelect) entryTypeSelect.value = entry.type ?? 'ciudad';
if (entryDescriptionTextarea) entryDescriptionTextarea.value = entry.description ?? '';
if (entryNotesTextarea) entryNotesTextarea.value = entry.notes ?? '';

renderVisualPanel(entry);
renderImagePreview(entry.image_path ?? '');
renderAttributes(entry.attributes ?? []);
updateActiveLink(entry.id ?? '');

const currentMode = entryForm?.dataset.viewMode || 'view';
const newUrl = `?entry=${encodeURIComponent(entry.id ?? '')}&mode=${encodeURIComponent(currentMode)}#editor-section`;
window.history.replaceState({}, '', newUrl);
    }

    entryLinks.forEach(function (link) {
        link.addEventListener('click', async function (event) {
            event.preventDefault();

            const entryId = link.dataset.entryId;

            if (!entryId) {
                return;
            }

            try {
                await loadEntry(entryId);
            } catch (error) {
                console.error(error);
            }
        });
    });
});
</script>

</body>
</html>
