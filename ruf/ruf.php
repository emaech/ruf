<?php
/**
 * Plugin Name: Rename Uploaded Files (RUF)
 * Description: Renames uploaded files and updates database references.
 * Version: 1.7
 * Author: emaech
 */

// Add a menu page to access the renaming tool.
add_action('admin_menu', function() {
    add_media_page(
        'Rename Uploaded Files',
        'Rename Files',
        'manage_options',
        'rename-uploaded-files',
        'ruf_render_tool_page'
    );
});

// Render the tool page.
function ruf_render_tool_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

	global $wpdb;


	// Single filename update.
    if (isset($_POST['ruf_file_id']) && isset($_POST['ruf_new_name'])) {
        $file_id = intval($_POST['ruf_file_id']);
        $new_name = sanitize_text_field(str_replace(' ', '-', $_POST['ruf_new_name']));
		
        if (ruf_rename_file($file_id, $new_name)) {
            echo '<div class="updated"><p>File renamed successfully!</p></div>';
        } else {
            echo '<div class="error"><p>Failed to rename the file.</p></div>';
        }
    }


	// Batch filename update.
	if (isset($_POST['ruf_replace_string']) ) { 
		$replace = sanitize_text_field($_POST['ruf_replace_string']);;
		$with = sanitize_text_field($_POST['ruf_with_string']);
		
		// Get all attachments
		$attachments = get_posts([ 'post_type' => 'attachment', 'numberposts' => -1 ]);
		$attachment_data = [];

		// Initialize a flag to track updates
		$updated = false;
	
		// Load all the attachments into an array
		foreach ($attachments as $attachment) {
			$attachment_data[] = [
				'ID' => $attachment->ID,
				'file_name' => basename(get_attached_file($attachment->ID)),
			];
		}
    
		// Filter the array where file_name contains the search string
		foreach ($attachment_data as $key => $data) {
			// Compare the file name with the search term
			if (strpos(strtolower($data['file_name']), strtolower($replace)) !== false) {
				
				// Get the file extension of the attachment
				$ext = '.' . pathinfo($data['file_name'], PATHINFO_EXTENSION);
				
				// Replace the string in the file name in the array
				$new_file_name = str_ireplace($replace, $with, $data['file_name']);
				$new_file_name_no_ext = str_replace($ext,'',$new_file_name);
				
				
				
				// Update the value in the array
				$attachment_data[$key]['file_name'] = $new_file_name_no_ext;

				$updated = true; // Set flag to true when an update happens
				
				
				// Call the rename function and update the file.
				ruf_rename_file($data['ID'], $new_file_name_no_ext); // Exensions are added later.
					
					
			}
		}
	
	
		// Check if any update happened
		if ($updated) {
			echo '<div class="updated"><p>Files renamed successfully!</p></div>';
		} else {
			echo '<div class="error"><p>Failed to rename any files.</p></div>';
		}

}



    ?>
    <div class="wrap">
        <h1>Rename Uploaded Files (RUF)</h1>
		<img src="<?php echo plugins_url('ruf.png', __FILE__); ?>" alt="RUF! Rename Uploaded Files">
        <hr>
		
		<form method="post">
		<h2>Rename One File</h2>
		<pre>Do not include the file extension (e.g. ".jpg") in your new name.</pre>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ruf_file_id">File</label></th>
                    <td>
                        <select name="ruf_file_id" id="ruf_file_id" required>
                            <?php
                            $attachments = get_posts([ 'post_type' => 'attachment', 'numberposts' => -1 ]);
                            foreach ($attachments as $attachment) {
                                echo '<option value="' . $attachment->ID . '">' . esc_html($attachment->post_title) . ' (' . basename(get_attached_file($attachment->ID)) . ')</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ruf_new_name">New File Name</label></th>
                    <td><input name="ruf_new_name" type="text" id="ruf_new_name" required /></td>
                </tr>
            </table>
			
            <p class="submit">
                <input type="submit" class="button-primary" value="Rename File" />
            </p>
        </form>
		<hr>
		<form method="post">
            <h2>Batch Rename Files</h2>
			<pre>Warning! Engage your brain first! This may take a long time to complete and impact a lot of files!</pre>
			<pre>This is a string replacement. e.g. it will replace "ABC" with "XYZ".</pre>
			<pre>Don't navigate away while this is running. It won't be good.</pre>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ruf_replace_string">Replace</label></th>
                    <td><input name="ruf_replace_string" type="text" id="ruf_replace_string" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ruf_with_string">With (Leave blank to remove)</label></th>
                    <td><input name="ruf_with_string" type="text" id="ruf_with_string" /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="Batch Rename Files" />
            </p>
        </form>
		
		
    </div>
    <?php
}

// Function to rename the file and update database references.
function ruf_rename_file($file_id, $new_name) {
    $file = get_post($file_id);
    if (!$file || $file->post_type !== 'attachment') {
        return false;
    }

    global $wpdb;

    $old_path = get_attached_file($file_id);
    $upload_dir = wp_upload_dir();

    // Ensure the new name does not include an extension; use the old extension.
    $ext = pathinfo($old_path, PATHINFO_EXTENSION);
    $new_basename = $new_name . '.' . $ext;

    // Define the new path.
    $new_path = trailingslashit($upload_dir['basedir']) . _wp_relative_upload_path($old_path);
    $new_path = str_replace(basename($old_path), $new_basename, $new_path);

    // Rename the main file.
    if (!rename($old_path, $new_path)) {
        return false;
    }

    // Rename thumbnails and webp versions.
    $meta = wp_get_attachment_metadata($file_id);
    $size_variations = [];
    if (isset($meta['sizes']) && is_array($meta['sizes'])) {
        foreach ($meta['sizes'] as $size => $info) {
            $old_thumb = str_replace(basename($old_path), $info['file'], $old_path);

            // Extract dimensions from the filename if applicable.
            $dimension_part = pathinfo($info['file'], PATHINFO_FILENAME);
            if (preg_match('/-(\d+x\d+)$/', $dimension_part, $matches)) {
                $dimension_suffix = $matches[1];
                $new_thumb_name = $new_name . '-' . $dimension_suffix . '.' . $ext;
                $new_thumb = str_replace(basename($old_path), $new_thumb_name, $old_path);

                if (file_exists($old_thumb)) {
                    rename($old_thumb, $new_thumb);
                    $meta['sizes'][$size]['file'] = basename($new_thumb);

                    // Handle webp versions if they exist.
                    $old_webp = $old_thumb . '.webp';
                    $new_webp = $new_thumb . '.webp';
                    if (file_exists($old_webp)) {
                        rename($old_webp, $new_webp);
                    }

                    // Add to size variations for updating post_content.
                    $size_variations[] = basename($old_thumb);
                    $size_variations[] = basename($old_webp);
                }
            }
        }
    }

    // Handle the WebP version of the original file (without dimensions).
    $old_webp_main = $old_path . '.webp';
    $new_webp_main = $new_path . '.webp';
    if (file_exists($old_webp_main)) {
        rename($old_webp_main, $new_webp_main);
        $size_variations[] = basename($old_webp_main);
    }

    // Update the attachment metadata.
    wp_update_attachment_metadata($file_id, $meta);

    // Update database references in the posts table.
    $old_filename = basename($old_path);
    $new_filename = basename($new_path);
    $size_variations[] = $old_filename;

	// Drop the exension from old filename.
	$old_name = str_replace('.'.$ext,'',$old_filename);
	

    // Update post_content for embedded images and URLs.
    $old_url = wp_get_attachment_url($file_id);
    $new_url = str_replace(basename($old_url), $new_basename, $old_url);

    $replace_pairs = [];
    foreach ($size_variations as $old_variant) {
        $replace_pairs[$old_variant] = str_replace($old_name, $new_name, $old_variant);
    }


    foreach ($replace_pairs as $old_variant => $new_variant) {
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)",
                $old_variant,
                $new_variant
            )
        );
    }

    // Update the GUID in the posts table.
    $wpdb->update(
        $wpdb->posts,
        ['guid' => str_replace(basename($old_path), $new_basename, $file->guid)],
        ['ID' => $file_id]
    );

    // Update the attached file path.
    update_attached_file($file_id, $new_path);

    return true;
}
