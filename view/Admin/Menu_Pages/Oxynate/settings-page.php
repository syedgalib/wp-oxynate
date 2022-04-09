<h2><?php _e( 'Settings', 'wp-oxynate' ) ?></h2>

<div class="wrap">
    <form method="post" name="wp-oxynate-settings-options">

        <table class="form-table" role="presentation">
            <tbody>
                <?php foreach( $data['option_fields'] as $option_key => $option_args ) : ?>
                <tr>
                    <th scope="row">
                        <label for="blogname">
                            <?php echo $option_args['label'] ?>
                        </label>
                    </th>
                    
                    <td>
                        <?php $data['fields_template']::get_field( $option_key, $option_args ); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>


        <!-- Submit Button -->
        <p class="submit">
            <?php wp_nonce_field( 'wp_oxinate_submit_settings_options' ) ?>
            
            <input 
                type="submit" 
                name="submit" 
                id="submit" 
                class="button button-primary" 
                value="Save Changes"
            >
        </p>
    </form>
</div>