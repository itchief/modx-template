<?php
/**
 * Chunk English lexicon topic
 *
 * @language en
 * @package modx
 * @subpackage lexicon
 */

// Entry out of alpha order because it must come before the entry it's used in below
$_lang['example_tag_chunk_name'] = 'NameOfChunk';

$_lang['chunk'] = 'Darab';
$_lang['chunk_category_desc'] = 'Use to group Chunks within the Elements tree.';
$_lang['chunk_code'] = 'Chunk Code (HTML)';
$_lang['chunk_description_desc'] = 'Usage information for this Chunk shown in search results and as a tooltip in the Elements tree.';
$_lang['chunk_delete_confirm'] = 'Biztosan törölni szeretné ezt a kódegységet?';
$_lang['chunk_duplicate_confirm'] = 'Biztosan kettőzni szeretné ezt a kódegységet?';
$_lang['chunk_err_create'] = 'Hiba történt a kódegység törlése közben.';
$_lang['chunk_err_duplicate'] = 'Hiba a kódegység kettőzése közben.';
$_lang['chunk_err_ae'] = 'Van már kódegység "[[+name]]" névvel.';
$_lang['chunk_err_invalid_name'] = 'A megadott kódegységnév érvénytelen.';
$_lang['chunk_err_locked'] = 'A kódegység zárolt.';
$_lang['chunk_err_remove'] = 'Hiba történt a kódegység törlése közben.';
$_lang['chunk_err_save'] = 'Hiba történt a kódegység mentésekor.';
$_lang['chunk_err_nf'] = 'Kódegység nem található!';
$_lang['chunk_err_nfs'] = 'Kódegység nem található [[+id]] azonosítóval';
$_lang['chunk_err_ns'] = 'Kódegység nincs megadva.';
$_lang['chunk_err_ns_name'] = 'Kérjük, adjon meg egy nevet.';
$_lang['chunk_lock'] = 'Kódegység zárolása szerkesztésre';
$_lang['chunk_lock_desc'] = 'Only users with “edit_locked” permissions can edit this Chunk.';
$_lang['chunk_name_desc'] = 'Place the content generated by this Chunk in a Resource, Template, or other Chunk using the following MODX tag: [[+tag]]';
$_lang['chunk_new'] = 'Kódegység létrehozása';
$_lang['chunk_properties'] = 'Alaptulajdonságok';
$_lang['chunk_tab_general_desc'] = 'Here you can enter the basic attributes for this <em>Chunk</em> as well as its content. The content must be HTML, either placed in the <em>Chunk Code</em> field below or in a static external file, and may include MODX tags. Note, however, that PHP code will not run in this element.';
$_lang['chunk_tag_copied'] = 'Chunk tag copied!';
$_lang['chunk_title'] = 'Kódegység létrehozása, módosítása';
$_lang['chunk_untitled'] = 'Névtelen kódegység';
$_lang['chunks'] = 'Kódegységek';

// Temporarily match old keys to new ones to ensure compatibility
// --fields
$_lang['chunk_desc_category'] = $_lang['chunk_category_desc'];
$_lang['chunk_desc_description'] = $_lang['chunk_description_desc'];
$_lang['chunk_desc_name'] = $_lang['chunk_name_desc'];
$_lang['chunk_lock_msg'] = $_lang['chunk_lock_desc'];

// --tabs
$_lang['chunk_msg'] = $_lang['chunk_tab_general_desc'];
