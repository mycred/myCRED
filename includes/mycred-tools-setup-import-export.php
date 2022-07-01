<?php
if( !class_exists( 'myCRED_Setup_Import_Export' ) ):
class myCRED_Setup_Import_Export
{
    public $mycred_tools_import_export;

    public function __construct()
    {
        
    }

    public function get_setup_page()
    {
        $this->mycred_tools_import_export = new myCRED_Tools_Import_Export();
        ?>
        <input type="hidden" class="request-tab" value="<?php if( isset( $_GET['mycred-tools'] ) ) echo sanitize_key( $_GET['mycred-tools'] );?>" />
        <form action="" enctype="multipart/form-data" class="mycred-tools-setup">
        <h3><?php esc_html_e( 'Setup', 'mycred' ); ?></h3>
        <?php
            if( ! empty( $this->mycred_tools_import_export->core_point_types ) ) :?> 
                <div>
                    <div>
                        <label class="mycred-switch1">
                            <input type="checkbox" value="all-points" name="all_points" id="all-points">
                            <span class="slider round"></span>
                        </label> 
                        <label for="all-points"><b>All Point Types</b></label>
                    </div>
                    <?php foreach( $this->mycred_tools_import_export->core_point_types as $key => $value ):?>
                        <ol>
                            <li>
                                <label class="mycred-switch1">
                                    <input type="checkbox" value="<?php echo esc_attr( $key );?>" name="point_type" id="<?php echo esc_attr( $key );?>-point">
                                    <span class="slider round"></span>
                                </label> 
                                <label for="<?php echo esc_attr( $key );?>-point"><b><?php echo esc_html( $value );?></b></labal>
                            </li>
                            <ol>
                                <li>
                                    <label class="mycred-switch1">
                                        <input type="checkbox" value="<?php echo esc_attr( $key );?>" name="hooks" id="<?php echo esc_attr( $key );?>-hooks">
                                        <span class="slider round"></span>
                                    </label> 
                                    <label for="<?php echo esc_attr( $key );?>-hooks">Hooks</labal>
                                </li>
                                <li>
                                    <label class="mycred-switch1">
                                        <input type="checkbox" value="<?php echo esc_attr( $key );?>" name="ranks" id="<?php echo esc_attr( $key );?>-ranks">
                                        <span class="slider round"></span>
                                    </label> 
                                    <label for="<?php echo esc_attr( $key );?>-ranks">Ranks</labal>
                                </li>
                            </ol>
                        </ol>
                    <?php endforeach;?>
                </div>
            <?php endif;?>

            <?php if( !empty( $this->mycred_tools_import_export->get_badge_categories() ) ):?>
                <ul>
                    <li>
                        <label class="mycred-switch1">
                            <input type="checkbox" value="all-achievements" name="all_achievements" id="all-achievements">
                            <span class="slider round"></span>
                        </label> 
                        <label for="all-achievements"><b>All Achievement Types</b></label>
                    </li>
                    <ol>
                    <?php foreach( $this->mycred_tools_import_export->get_badge_categories() as $category ):?>
                        <li>
                            <label class="mycred-switch1">
                                <input type="checkbox" value="<?php echo esc_attr( $category->cat_ID );?>" name="achievements" id="cate-<?php echo esc_attr( $category->cat_ID );?>">
                                <span class="slider round"></span>
                            </label> 
                            <label for="cate-<?php echo esc_attr( $category->cat_ID );?>"><b><?php echo esc_html( $category->name );?></b></label>
                        </li>
                        <?php 

                        $badges = mycred_get_badges_by_term_id( $category->cat_ID );

                        foreach( $badges as $badge ):?>
                        <ol>
                            <li>
                                <label class="mycred-switch1">
                                    <input type="checkbox" value="<?php echo esc_attr( $badge->ID );?>" name="badge_<?php echo esc_attr( $category->cat_ID );?>" id="badge-<?php echo esc_attr( $badge->ID );?>-<?php echo esc_attr( $category->cat_ID );?>">
                                    <span class="slider round"></span>
                                </label> 
                                <label for="badge-<?php echo esc_attr( $badge->ID );?>-<?php echo esc_attr( $category->cat_ID );?>"><b><?php echo esc_html( $badge->post_title );?></b></label>
                            </li>
                            <li>
                                <label class="mycred-switch1">
                                    <input type="checkbox" value="<?php echo esc_attr( $badge->ID );?>" name="levels_<?php echo esc_attr( $category->cat_ID );?>" id="level-<?php echo esc_attr( $badge->ID );?>-<?php echo esc_attr( $category->cat_ID );?>">
                                    <span class="slider round"></span>
                                </label> 
                                <label for="level-<?php echo esc_attr( $badge->ID );?>-<?php echo esc_attr( $category->cat_ID );?>">Levels</label>
                            </li>
                        </ol>
                        <?php endforeach;?>
                    <?php endforeach;?>
                    </ol>
                </ul>
            <?php endif;?>

            <?php

            $un_cat_badges = $this->mycred_tools_import_export->get_uncat_badge_ids();

            if ( ! empty( $un_cat_badges ) ):?>
                <ul>
                    <li>
                        <label class="mycred-switch1">
                            <input type="checkbox" value="uncat-achievements" name="uncat_achievements" id="uncat-achievements">
                            <span class="slider round"></span>
                        </label> 
                        <label for="uncat-achievements"><b>Uncategorized Achievements</b></label>
                    </li>
                    <?php foreach( $un_cat_badges as $data ):?>
                    <ol>
                        <li>
                            <label class="mycred-switch1">
                                <input type="checkbox" value="<?php echo esc_attr( $data['ID'] ); ?>" name="badge" id="uncat-badge-<?php echo esc_attr( $data['ID'] ); ?>">
                                <span class="slider round"></span>
                            </label> 
                            <label for="uncat-badge-<?php echo esc_attr( $data['ID'] ); ?>"><b><?php echo esc_html( get_the_title( $data['ID'] ) ); ?></b></label>
                        </li>
                        <li>
                            <label class="mycred-switch1">
                                <input type="checkbox" value="<?php echo esc_attr( $data['ID'] ); ?>" name="levels" id="uncat-level-<?php echo esc_attr( $data['ID'] ); ?>">
                                <span class="slider round"></span>
                            </label> 
                            <label for="uncat-level-<?php echo esc_attr( $data['ID'] ); ?>">Levels</label>
                        </li>
                    </ol>
                    <?php endforeach;?>
                </ul>
            <?php endif;?>

            <button class="button button-primary" id="export-raw">
                <span class="dashicons dashicons-database-export v-align-middle"></span> <?php esc_html_e( 'Export Setup', 'mycred' ); ?>
            </button>
        </form>

        <form action="" enctype="multipart/form-data" class="mycred-tools-setup-import">
            <h3><?php esc_html_e( 'Import', 'mycred' ); ?></h3>
            <input type="file" id="import-file" name="file" accept=".json" />
            <button class="button button-primary", id="import">
                <span class="dashicons dashicons-database-import v-align-middle"></span> <?php esc_html_e( 'Import Setup','mycred' ); ?>
            </button>
            <p><i>
                <?php esc_html_e( 'Accepts JSON format.', 'mycred' ); ?>
            </i></p>
        </form>
        <div style="clear: both;"></div>
        <?php 

    }

    public function export_setup( $post_field )
    {
        $this->mycred_tools_import_export = new myCRED_Tools_Import_Export();

        $prepare_data = array();

        $achievement = '';

        //Making Data Compatible
        foreach( $post_field as $key => $type )
        {
            if( isset( $type['point_type'] ) )
            {
                $prepare_data['point_types'][$type['point_type']] = array();
                $prepare_data['point_types'][$type['point_type']][] = 'pref_core';
            }

            if( isset( $type['hooks'] ) )
            {
                $prepare_data['point_types'][$type['hooks']][] = 'hooks';
            }

            if( isset( $type['ranks'] ) )
            {
                $prepare_data['point_types'][$type['ranks']][] = 'ranks';
            }

            //Making Achievements' key
            if( isset( $type['achievements'] ) )
            {
                $prepare_data['achievements']["achievement_{$type["achievements"]}"] = array( 'achievement' => $type['achievements'] );
                $achievement = $type['achievements'];
            }

            //Storing Badges into achievements key
            if( array_key_exists( "badge_{$achievement}", $type ) )
            {
                $prepare_data['achievements']["achievement_{$achievement}"]["badge_{$type["badge_{$achievement}"]}"] = $type["badge_{$achievement}"];
            }

            //Storing levels into achievements key
            if( array_key_exists( "levels_{$achievement}", $type ) )
            {
                $prepare_data['achievements']["achievement_{$achievement}"]["levels_{$type["levels_{$achievement}"]}"] = $type["levels_{$achievement}"];
            }

            //Storing badges with no Achievement/ Category
            if( isset( $type['badge'] ) )
            {
                $prepare_data['badges'][$type['badge']][] = 'badge';
            }

            //Storing Levles with no Achievement/ Category
            if( isset( $type['levels'] ) )
            {
                $prepare_data['badges'][$type['levels']][] = 'levels';
            }
        }

        //Preparing CSV
        $prep_raw = array();
        $counter = 0;

        foreach( $prepare_data as $key => $type )
        {
            //Point types
            if( $key == 'point_types' )
            {
                foreach( $type as $pt => $value )
                {
                    $prep_raw[$counter]['point_type_key'] = $pt;

                    $prep_raw[$counter]['point_type_name'] = mycred_get_point_type_name( $pt );

                    $pref_hooks = MYCRED_DEFAULT_TYPE_KEY == $pt ? 'mycred_pref_core' : "mycred_pref_core_{$pt}";

                    $prep_raw[$counter]['pref_core'] = serialize( mycred_get_option( $pref_hooks ) );


                    if( in_array( 'hooks', $value ) )
                    {
                        $pref_hooks = MYCRED_DEFAULT_TYPE_KEY == $pt ? 'mycred_pref_hooks' : "mycred_pref_hooks_{$pt}";

                        $prep_raw[$counter]['hooks'] = serialize( mycred_get_option( $pref_hooks ) );
                    }

                    if( in_array( 'ranks', $value ) )
                    {
                        $prep_raw[$counter]['ranks'] = serialize( mycred_get_ranks( 'publish', '-1', 'DESC', $pt ) );
                    }

                    $counter++;
                }
            }

            if( $key == 'achievements' )
            {
                foreach( $type as $achievement => $value )
                {
                    foreach( $value as $badge_level => $bl_value )
                    {
                        if( $badge_level == 'achievement' )
                        {
                            $prep_raw['achievements']["achievement_{$bl_value}"] =  get_the_category_by_ID( $bl_value );
                        }

                        if( strpos( $badge_level, 'badge' ) !== false )
                        {
                            $prep_raw['achievements'][$bl_value]['badge'] =  serialize( get_post( $bl_value ) );
                            
                            if( wp_get_attachment_url( mycred_get_post_meta( $bl_value, 'main_image', true ) ) )
                                $prep_raw['achievements'][$bl_value]['thumbnail'] = wp_get_attachment_url( mycred_get_post_meta( $bl_value, 'main_image', true ) );

                            $metas = array();

                            $metas['manual_badge'] = mycred_get_post_meta( $bl_value, 'manual_badge' );
                            $metas['open_badge'] = mycred_get_post_meta( $bl_value, 'open_badge' );
                            $metas['congratulation_msg'] = mycred_get_post_meta( $bl_value, 'congratulation_msg' );
                            $metas['mycred_badge_align'] = mycred_get_post_meta( $bl_value, 'mycred_badge_align' );
                            $metas['mycred_layout_check'] = mycred_get_post_meta( $bl_value, 'mycred_layout_check' );

                            $prep_raw['achievements'][$bl_value]['levels_meta']['metas'] = serialize( $metas );

                        }
                        
                        if( strpos( $badge_level, 'levels' ) !== false )
                        {
                            $badge_prefs = unserialize( mycred_get_post_meta( $bl_value, '' )['badge_prefs'][0] );
                            
                            //Changing Attachment ID with image URL
                            foreach( $badge_prefs as $key => $value )
                            {
                                if( isset( $value['attachment_id'] ) )
                                {
                                    $badge_prefs[$key]['attachment_id'] = wp_get_attachment_url( $value['attachment_id'] );
                                }
                            } 

                            $levels_meta = mycred_get_post_meta( $bl_value, '' );

                            $levels_meta['badge_prefs'][0] = serialize( $badge_prefs );

                            $prep_raw['achievements'][$bl_value]['levels_meta']['levels'] =  serialize( $levels_meta );

                        }
                    }
                }
            }

            if( $key == 'badges' )
            {
                foreach( $type as $badge_level => $bl_value )
                {
                    if( in_array( 'badge', $bl_value ) )
                    {
                        $prep_raw['badges'][$badge_level]['badge'] =  serialize( get_post( $badge_level, '' ) );
                        
                        if( wp_get_attachment_url( mycred_get_post_meta( $badge_level, 'main_image', true ) ) )
                                $prep_raw['badges'][$badge_level]['thumbnail'] = wp_get_attachment_url( mycred_get_post_meta( $badge_level, 'main_image', true ) );
                    
                        $metas = array();

                        $metas['manual_badge'] = mycred_get_post_meta( $bl_value, 'manual_badge' );
                        $metas['open_badge'] = mycred_get_post_meta( $bl_value, 'open_badge' );
                        $metas['congratulation_msg'] = mycred_get_post_meta( $bl_value, 'congratulation_msg' );
                        $metas['mycred_badge_align'] = mycred_get_post_meta( $bl_value, 'mycred_badge_align' );
                        $metas['mycred_layout_check'] = mycred_get_post_meta( $bl_value, 'mycred_layout_check' );

                        $prep_raw['badges'][$badge_level]['levels_meta']['metas'] = serialize( $metas );
                    }

                    if( in_array( 'levels', $bl_value ) )
                    {
                        if( array_key_exists( 'badge_prefs' , mycred_get_post_meta( $badge_level, '' ) ) )
                        {
                            $badge_prefs = unserialize( mycred_get_post_meta( $badge_level, '' )['badge_prefs'][0] );

                                //Changing Attachment ID with image URL
                            foreach( $badge_prefs as $key => $value )
                            {
                                if( isset( $value['attachment_id'] ) )
                                {
                                    $badge_prefs[$key]['attachment_id'] = wp_get_attachment_url( $value['attachment_id'] );
                                }
                            } 

                            $prep_raw['badges'][$badge_level]['levels_meta']['levels'] = serialize( $badge_prefs );
                        }
                    }
                }
            }
        }
        
        echo json_encode( $prep_raw );

        die;
    }

    public function import_setup_json( $file_path = '' )
    {
        $this->mycred_tools_import_export = new myCRED_Tools_Import_Export();

        $json_string = file_get_contents( $file_path );
        
        $data = json_decode( preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $json_string ), true );

        foreach( $data as $data_key => $data_value  )
        {
            //Import Points/ Hooks/ Ranks
            if( is_int( $data_key ) )
            {
                $pt_key = $data_value['point_type_key'];
                
                if( isset( $data_value['point_type_key'] ) )
                {
                    
                    $core_pref = $data_value['pref_core'];

                    $option = MYCRED_DEFAULT_TYPE_KEY == $pt_key ? 'mycred_pref_core' : "mycred_pref_core_{$pt_key}";

                    mycred_update_option( $option, unserialize( $core_pref ) );

                    $existing_pts = mycred_get_option( 'mycred_types' );

                    $existing_pts[$data_value['point_type_key']] = $data_value['point_type_name'];

                    mycred_update_option( 'mycred_types', $existing_pts );
                    
                }

                //Import Hooks
                if( isset( $data_value['hooks'] ) )
                {
                    $hooks = $data_value['hooks'];

                    $option_name = $pt_key == MYCRED_DEFAULT_TYPE_KEY ? 'mycred_pref_hooks' : "mycred_pref_hooks_{$pt_key}";

			        mycred_update_option( $option_name, unserialize( $hooks ) );
                }

                //Import Ranks
                if( isset( $data_value['ranks'] ) )
                {
                    $ranks = unserialize( $data_value['ranks'] );

                    foreach( $ranks as $rank_key => $rank )
                    {
                        $min = $rank->minimum;

                        $max = $rank->maximum;

                        $ctype = $rank->point_type->cred_id;

                        $logo_url = '';

                        $attachment_id = '';

                        $args = array(
                            'post_author'       =>  get_current_user_id(),
                            'post_date'         =>  $rank->post->post_date,
                            'post_date_gmt'     =>  $rank->post->post_date_gmt,
                            'post_content'      =>  $rank->post->post_content,
                            'post_title'        =>  $rank->post->post_title,
                            'post_status'       =>  $rank->post->post_status,
                            'post_name'         =>  $rank->post->post_name,
                            'post_modified'     =>  $rank->post->post_modified,
                            'post_modified_gmt' =>  $rank->post->post_modified_gmt,
                            'post_type'         =>  $rank->post->post_type
                        );

                        $post_id = wp_insert_post( $args );

                        if( $rank->logo_url != null )
                        {
                            $logo_url = $rank->logo_url;

                            $attachment_id = $this->mycred_tools_import_export->insert_attachment_from_url( $logo_url );
                        }

                        mycred_update_post_meta( $post_id, 'mycred_rank_min', $min );
                        mycred_update_post_meta( $post_id, 'mycred_rank_max', $max );
                        mycred_update_post_meta( $post_id, 'ctype', $ctype );
                        mycred_update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
                    }
                }
            }
            
            //Achievement
            if( $data_key == 'achievements' )
            {

                $cat_id = '';
                $post_id = '';

                foreach( $data_value as $achievement_key => $achievement_value )
                {
                    if( strpos( $achievement_key, 'achievement' ) !== false )
                    {
                        $catarr = array(
                            'taxonomy'          =>  MYCRED_BADGE_CATEGORY,
                            'cat_name'          =>  $achievement_value
                        ); 

                        $cat_id = wp_insert_category( $catarr );
                    }

                    if( is_int( $achievement_key ) )
                    {
                        //Badge
                        if( isset( $achievement_value['badge'] ) )
                        {

                            $attachment_id = '';

                            $badge = unserialize( $achievement_value['badge'] );
                            
                            $args = array(
                                'post_author'       =>  get_current_user_id(),
                                'post_date'         =>  $badge->post_date,
                                'post_date_gmt'     =>  $badge->post_date_gmt,
                                'post_content'      =>  $badge->post_content,
                                'post_title'        =>  $badge->post_title,
                                'post_status'       =>  $badge->post_status,
                                'post_name'         =>  $badge->post_name,
                                'post_modified'     =>  $badge->post_modified,
                                'post_modified_gmt' =>  $badge->post_modified_gmt,
                                'post_type'         =>  $badge->post_type
                            );
                            
                            $post_id = wp_insert_post( $args );

                            wp_set_object_terms( $post_id, $cat_id, MYCRED_BADGE_CATEGORY );

                            if( isset( $achievement_value['thumbnail'] ) )
                            {    
                                $attachment_id = $this->mycred_tools_import_export->insert_attachment_from_url( $achievement_value['thumbnail'] );
                            
                                mycred_update_post_meta( $post_id, 'main_image', $attachment_id );

                            }
                        }

                        //Level
                        if( isset( $achievement_value['levels_meta'] ) )
                        {
                            $level_metas = $achievement_value['levels_meta'];
                            $metas = unserialize( $level_metas['metas'] );
                           
                            if( isset( $achievement_value['levels_meta']['levels'] ) )
                            {
                                $levels = unserialize( $achievement_value['levels_meta']['levels'] );
                                $levels = unserialize( $levels['badge_prefs'][0] );
                                
                                foreach( $levels as $key => $l_value )
                                {
                                    if( isset( $l_value['attachment_id'] ) )
                                        $levels[$key]['attachment_id'] = $this->mycred_tools_import_export->insert_attachment_from_url( $l_value['attachment_id'] );
                                        
                                } 
                                
                                mycred_update_post_meta( $post_id, 'badge_prefs', $levels );
                            }
                            
                            mycred_update_post_meta( $post_id, 'manual_badge', $metas['manual_badge'][0] );
                            mycred_update_post_meta( $post_id, 'open_badge', $metas['open_badge'][0] );
                            mycred_update_post_meta( $post_id, 'congratulation_msg', $metas['congratulation_msg'][0] );
                            mycred_update_post_meta( $post_id, 'mycred_badge_align', $metas['mycred_badge_align'][0] );
                            mycred_update_post_meta( $post_id, 'mycred_layout_check', $metas['mycred_layout_check'][0] );
                        }
                    }
                }
            }
            //Badges
            if( $data_key == 'badges' )
            {
                $post_id = '';

                foreach( $data_value as $badge_key => $badge_value )
                {
                    //Badge
                    if( isset( $badge_value['badge'] ) )
                    {
                        $post_id = '';

                        $badge = unserialize( $badge_value['badge'] );
                        
                        $args = array(
                            'post_author'       =>  get_current_user_id(),
                            'post_date'         =>  $badge->post_date,
                            'post_date_gmt'     =>  $badge->post_date_gmt,
                            'post_content'      =>  $badge->post_content,
                            'post_title'        =>  $badge->post_title,
                            'post_status'       =>  $badge->post_status,
                            'post_name'         =>  $badge->post_name,
                            'post_modified'     =>  $badge->post_modified,
                            'post_modified_gmt' =>  $badge->post_modified_gmt,
                            'post_type'         =>  $badge->post_type,
                        );
                        
                        $post_id = wp_insert_post( $args );

                        if( isset( $badge_value['thumbnail'] ) )
                        {    
                            $attachment_id = $this->mycred_tools_import_export->insert_attachment_from_url( $badge_value['thumbnail'] );
                        
                            mycred_update_post_meta( $post_id, 'main_image', $attachment_id );

                        }
                    }
                    
                    //Level
                    if( isset( $badge_value['levels_meta'] ) )
                    {
                        $level_metas = $badge_value['levels_meta'];
                        $metas = unserialize( $level_metas['metas'] );
                        
                        
                        if( isset( $level_metas['levels'] ) )
                        {
                            $levels = unserialize( $badge_value['levels_meta']['levels'] );
                            
                            foreach( $levels as $key => $l_value )
                            {
                                if( isset( $l_value['attachment_id'] ) )
                                    $levels[$key]['attachment_id'] = $this->mycred_tools_import_export->insert_attachment_from_url( $l_value['attachment_id'] );
                                    
                            } 
                            mycred_update_post_meta( $post_id, 'badge_prefs', $levels );
                        }
                        
                        mycred_update_post_meta( $post_id, 'manual_badge', $metas['manual_badge'] );
                        mycred_update_post_meta( $post_id, 'open_badge', $metas['open_badge'] );
                        mycred_update_post_meta( $post_id, 'congratulation_msg', $metas['congratulation_msg'] );
                        mycred_update_post_meta( $post_id, 'mycred_badge_align', $metas['mycred_badge_align'] );
                        mycred_update_post_meta( $post_id, 'mycred_layout_check', $metas['mycred_layout_check'] );

                    }
                }
            }
        }
        die;
    }
}
endif;