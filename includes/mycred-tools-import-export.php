<?php
if( !class_exists( 'myCRED_Tools_Import_Export' ) ):
class myCRED_Tools_Import_Export extends myCRED_Setup_Import_Export
{
    public $core_point_types;

    public function __construct()
    {
        $this->core_point_types = mycred_get_types();
        
        add_action( 'wp_ajax_mycred-tools-import-export', array( $this,'import_export' ) );
    }

    public function get_header()
    {
        $points = get_mycred_tools_page_url( 'points' );
        $badges = get_mycred_tools_page_url( 'badges' );
        $ranks = get_mycred_tools_page_url( 'ranks' );
        $setup = get_mycred_tools_page_url( 'setup' );

        $page = isset( $_GET['mycred-tools'] ) ? sanitize_text_field( wp_unslash( $_GET['mycred-tools'] ) ) : '';
        
        $heading = $_GET['mycred-tools'] == 'setup' ? __( 'Export','mycred' ) : __( 'Import','mycred' );

        echo  '<h1>' . esc_html( $heading ) . '</h1>';
        ?>
        
        <div class="subsubsub">
            <a href="<?php echo esc_url( $points ); ?>" class="<?php echo ( isset( $_GET['mycred-tools'] ) && $_GET['mycred-tools'] == 'points' ) ? 'current' : ''; ?>"><?php esc_html_e( 'Points','mycred' ); ?></a>
            <?php
            if( class_exists( 'myCRED_Badge' ) )
            {
                $current = ( isset( $_GET['mycred-tools'] ) && $_GET['mycred-tools'] == 'badges' ) ? 'current' : '';
                echo '| <a href="' . esc_url( $badges ) . '" class="' . esc_attr( $current ) . '"> Badges</a>';
            }

            if( class_exists( 'myCRED_Ranks_Module' ) )
            {
                $current = ( isset( $_GET['mycred-tools'] ) && $_GET['mycred-tools'] == 'ranks' ) ? 'current' : '';
                echo '| <a href="' . esc_url( $ranks ) . '" class="' . esc_attr( $current ) . '">Ranks</a>';
            }
            ?>

            | <a href="<?php echo esc_url( $setup ); ?>" class="<?php echo ( isset( $_GET['mycred-tools'] ) && $_GET['mycred-tools'] == 'setup' ) ? 'current' : ''; ?>"><?php esc_html_e( 'Setup','mycred' ); ?></a>

            <input type="hidden" class="request-tab" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['mycred-tools'] ) ) ); ?>" />
        </div>
        <br class="clear">
        <?php

        $this->get_body( $page );
    }

    public function get_body( $page )
    {
        //Points
        if( $page == 'points' )
            $this->get_points_page();

        //Badges
        if( $page == 'badges' )
            $this->get_badge_page();

        //Rank
        if( $page == 'ranks' )
            $this->get_rank_page();

        //Setup
        if( $page == 'setup' )
            $this->get_setup_page();
    }

    public function get_points_page()
    {
        $uf_options = array(
            'id'        =>  __( 'ID','mycred' ),
            'user_name' =>  __( 'Username','mycred' ),
            'email'     =>  __( 'Email','mycred' )
        );
            
        $uf_attr = array(
            'id'    =>  'tools-uf-import-export'
        );

        $pt_options = $this->core_point_types;
            
        $pr_attr = array(
            'id'        =>  'tools-type-import-export',
            'multiple'  =>  'multiple'
        );

        ?>
        <div class="mycred-tools-import-export">
            <h3><?php esc_html_e( 'User Points','mycred' ); ?></h3>
            <table>
                <tr>
                    <td><h4><?php esc_html_e( 'CSV File','mycred' ); ?></h4></td>
                    <td>
                        <form method="post" enctype="multipart/form-data">
                            <input type="file" id="import-file" name="file" accept=".csv" />
                            <button class="button button-primary", id="import">
                                <span class="dashicons dashicons-database-import v-align-middle"></span> <?php esc_html_e( 'Import User Points','mycred' ); ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>
                        
                    </td>
                    <td>
                        <button class="button button-secondary" id="download-raw-template-csv">
                            <span class="dashicons dashicons-download v-align-middle"></span> <?php esc_html_e( 'Download Raw Template','mycred' ); ?>
                        </button>

                        <button class="button button-secondary" id="download-formatted-template-csv">
                            <span class="dashicons dashicons-download v-align-middle"></span> <?php esc_html_e( 'Download Formatted Template','mycred' ); ?>
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <i><?php esc_html_e( 'Only raw  format can be Import.', 'mycred' ) ?></i>
                    </td>
                </tr>
            </table>
            <h1><?php esc_html_e( 'Export','mycred' ); ?></h1>
            <h3><?php esc_html_e( 'User Points','mycred' ); ?></h3>
            <table>
                <tr>
                    <td>
                    <?php esc_html_e( 'Select Point Types to be Exported','mycred' ); ?>
                    </td>
                    <td>
                        <button class="button button-secondary" id="select-all-pt">
                            <span class="dashicons dashicons-download v-align-middle"></span> <?php esc_html_e( 'Select/ Deselect All','mycred' ); ?>
                        </button>
                    </td>
                </tr>
            </table>

            <div class="mycred-container">
                <label><?php esc_html_e( 'Select Point Types','mycred' ); ?></label>
                <?php 
                    echo wp_kses( 
                        mycred_create_select2( $pt_options, $pr_attr ),
                        array(
                            'select' => array(
                                'id' => array(),
                                'style' => array(),
                                'multiple' => array()
                            ),
                            'option' => array(
                                'value' => array(),
                                'selected' => array()
                            ),
                        )
                    );
                ?>
            </div>

            <div class="mycred-container">
                <label><?php esc_html_e( 'User Field in Exported File', 'mycred' );?></label>
                <?php echo wp_kses(
                        mycred_create_select2( $uf_options, $uf_attr ),
                        array(
                            'select' => array(
                                'id' => array(),
                                'style' => array()
                            ),
                            'option' => array(
                                'value' => array(),
                                'selected' => array()
                            ),
                        )
                    );
                ?>
            </div>

            <div class="mycred-container">
                <button class="button button-primary" id="export-raw">
                    <span class="dashicons dashicons-database-export v-align-middle"></span> <?php esc_html_e( 'Export Raw', 'mycred' ); ?>
                </button>

                <button class="button button-primary" id="export-formatted">
                    <span class="dashicons dashicons-database-export v-align-middle"></span> <?php esc_html_e( 'Export Formatted', 'mycred' ); ?>
                </button>
            </div>
        </div>
        <?php
    }

    public function get_badge_page()
    {
        $uf_options = array(
            'id'        =>  __( 'ID','mycred' ),
            'user_name' =>  __( 'Username','mycred' ),
            'email'     =>  __( 'Email','mycred' )
        );
            
        $uf_attr = array(
            'id'    =>  'tools-uf-import-export'
        );

        $badges_options = array();

        $badge_ids = mycred_get_badge_ids();

        foreach( $badge_ids as $id )
            $badges_options[$id] = get_the_title( $id );
        
        $badges_attr = array(
            'id'        =>  'tools-type-import-export',
            'multiple'  =>  'multiple'
        );

        $badges_fields_options = array(
            'id'    =>  'ID',
            'title' =>  'Title',
            'slug'  =>  'Slug'
        );

        $badges_fields_attr = array(
            'id'        =>  'tools-badge-fields-import-export'
        );

        $type_options = array(
            'id'    =>  'ID',
            'slug'  =>  'Slug',
            'title' =>  'Title'
        );

        $type_attr = array(
            'id'    =>  'import-format-type'
        );

        ?>
        <div class="mycred-tools-import-export">
            <h3><?php esc_html_e( 'User Badges','mycred' ); ?></h3>
            <table>
                <tr>
                    <td><h4><?php esc_html_e( 'CSV File','mycred' ); ?></h4></td>
                    <td>
                        <form method="post" enctype="multipart/form-data">
                            <input type="file" id="import-file" name="file" accept=".csv" />
                            <?php echo wp_kses(
                                    mycred_create_select2( $type_options, $type_attr ),
                                    array(
                                        'select' => array(
                                            'id' => array(),
                                            'style' => array()
                                        ),
                                        'option' => array(
                                            'value' => array(),
                                            'selected' => array()
                                        ),
                                    )
                                ); 
                            ?>
                            <button class="button button-primary", id="import">
                                <span class="dashicons dashicons-database-import v-align-middle"></span> <?php esc_html_e( 'Import User Badges','mycred' ); ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>
                        
                    </td>
                    <td>
                        <button class="button button-secondary" id="download-raw-template-csv">
                            <span class="dashicons dashicons-download v-align-middle"></span> <?php esc_html_e( 'Download Raw Template','mycred' ); ?>
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <i><?php esc_html_e( 'Only raw format can be Import.', 'mycred' ) ?></i>
                    </td>
                </tr>
            </table>
            <h1><?php esc_html_e( 'Export','mycred' ); ?></h1>
            <h3><?php esc_html_e( 'User Badges','mycred' ); ?></h3>
            <table>
                <tr>
                    <td>
                    <?php esc_html_e( 'Select Badges to be Exported','mycred' ); ?>
                    </td>
                    <td>
                        <button class="button button-secondary" id="select-all-pt">
                            <span class="dashicons dashicons-download v-align-middle"></span> <?php esc_html_e( 'Select/ Deselect All','mycred' ); ?>
                        </button>
                    </td>
                </tr>
            </table>

            <div class="mycred-container">
                <label><?php esc_html_e( 'Select Badges','mycred' ); ?></label>
                <?php echo wp_kses(
                        mycred_create_select2( $badges_options, $badges_attr ),
                        array(
                            'select' => array(
                                'id' => array(),
                                'style' => array(),
                                'multiple' => array()
                            ),
                            'option' => array(
                                'value' => array(),
                                'selected' => array()
                            ),
                        )
                    ); 
                ?>
            </div>

            <div class="mycred-container">
                <label><?php esc_html_e( 'User Field in Exported File', 'mycred' ); ?></label>
                <?php echo wp_kses(
                        mycred_create_select2( $uf_options, $uf_attr ),
                        array(
                            'select' => array(
                                'id' => array(),
                                'style' => array()
                            ),
                            'option' => array(
                                'value' => array(),
                                'selected' => array()
                            ),
                        )
                    ); 
                ?>
            </div>

            <div class="mycred-container">
                <label><?php esc_html_e( 'Badge Fields in Exported File', 'mycred' ); ?></label>
                <?php echo wp_kses(
                        mycred_create_select2( $badges_fields_options, $badges_fields_attr ),
                        array(
                            'select' => array(
                                'id' => array(),
                                'style' => array()
                            ),
                            'option' => array(
                                'value' => array(),
                                'selected' => array()
                            ),
                        )
                    ); 
                ?>
            </div>

            <div class="mycred-container">
                <button class="button button-primary" id="export-raw">
                    <span class="dashicons dashicons-database-export v-align-middle"></span> <?php esc_html_e( 'Export Raw', 'mycred' ); ?>
                </button>
            </div>
        </div>
        <?php
    }

    public function get_rank_page()
    {
        $uf_options = array(
            'id'        =>  __( 'ID','mycred' ),
            'user_name' =>  __( 'Username','mycred' ),
            'email'     =>  __( 'Email','mycred' )
        );
            
        $uf_attr = array(
            'id'    =>  'tools-uf-import-export'
        );

        $ranks_options = array();

            foreach( $this->core_point_types as $key => $value )
            {
                $_ranks = mycred_get_ranks( 'publish', '-1', 'ASC', $key );

                foreach( $_ranks as $key => $value )
                {
                    $ranks_options[$value->post->ID] = $value->post->post_title;
                }
            }

            $ranks_attr = array(
                'id'        =>  'tools-type-import-export',
                'multiple'  =>  'multiple'
            );

            $ranks_fields_options = array(
                'id'    =>  'ID',
                'title' =>  'Title',
                'slug'  =>  'Slug'
            );

            $ranks_fields_attr = array(
                'id'        =>  'tools-badge-fields-import-export'
            );

            $type_options = array(
                'id'    =>  'ID',
                'slug'  =>  'Slug',
                'title' =>  'Title'
            );

            $type_attr = array(
                'id'    =>  'import-format-type'
            );

            ?>
            <div class="mycred-tools-import-export">
                <h3><?php esc_html_e( 'User Ranks','mycred' ); ?></h3>
                <table>
                    <tr>
                        <td><h4><?php esc_html_e( 'CSV File','mycred' ); ?></h4></td>
                        <td>
                            <form method="post" enctype="multipart/form-data">
                                <input type="file" id="import-file" name="file" accept=".csv" />
                                <?php echo wp_kses(
                                        mycred_create_select2( $type_options, $type_attr ),
                                        array(
                                            'select' => array(
                                                'id' => array(),
                                                'style' => array()
                                            ),
                                            'option' => array(
                                                'value' => array(),
                                                'selected' => array()
                                            ),
                                        )
                                    ); 
                                ?>
                                <button class="button button-primary", id="import">
                                    <span class="dashicons dashicons-database-import v-align-middle"></span> <?php esc_html_e( 'Import User Ranks','mycred' ); ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            
                        </td>
                        <td>
                            <button class="button button-secondary" id="download-raw-template-csv">
                                <span class="dashicons dashicons-download v-align-middle"></span> <?php esc_html_e( 'Download Raw Template','mycred' ); ?>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <i><?php esc_html_e( 'Make sure Ranks\' Behaviour is set to Manual Mode, Only Raw format can be Import.', 'mycred' ) ?></i>
                        </td>
                    </tr>
                </table>
                <h1><?php esc_html_e( 'Export','mycred' ); ?></h1>
                <h3><?php esc_html_e( 'User Ranks','mycred' ); ?></h3>
                <table>
                    <tr>
                        <td>
                        <?php esc_html_e( 'Select Ranks to be Exported','mycred' ); ?>
                        </td>
                        <td>
                            <button class="button button-secondary" id="select-all-pt">
                                <span class="dashicons dashicons-download v-align-middle"></span> <?php esc_html_e( 'Select/ Deselect All','mycred' ); ?>
                            </button>
                        </td>
                    </tr>
                </table>

                <div class="mycred-container">
                    <label><?php esc_html_e( 'Select Ranks','mycred' ); ?></label>
                    <?php echo wp_kses(
                            mycred_create_select2( $ranks_options, $ranks_attr ),
                            array(
                                'select' => array(
                                    'id' => array(),
                                    'style' => array(),
                                    'multiple' => array()
                                ),
                                'option' => array(
                                    'value' => array(),
                                    'selected' => array()
                                ),
                            )
                        ); 
                    ?>
                </div>

                <div class="mycred-container">
                    <label><?php esc_html_e( 'User Field in Exported File', 'mycred' ); ?></label>
                    <?php echo wp_kses(
                            mycred_create_select2( $uf_options, $uf_attr ),
                            array(
                                'select' => array(
                                    'id' => array(),
                                    'style' => array()
                                ),
                                'option' => array(
                                    'value' => array(),
                                    'selected' => array()
                                ),
                            )
                        ); 
                    ?>
                </div>

                <div class="mycred-container">
                    <label><?php esc_html_e( 'Rank Fields in Exported File', 'mycred' ); ?></label>
                    <?php echo wp_kses(
                            mycred_create_select2( $ranks_fields_options, $ranks_fields_attr ),
                            array(
                                'select' => array(
                                    'id' => array(),
                                    'style' => array()
                                ),
                                'option' => array(
                                    'value' => array(),
                                    'selected' => array()
                                ),
                            )
                        ); 
                    ?>
                </div>

                <div class="mycred-container">
                    <button class="button button-primary" id="export-raw">
                        <span class="dashicons dashicons-database-export v-align-middle"></span> <?php esc_html_e( 'Export Raw', 'mycred' ); ?>
                    </button>
                </div>
            </div>
            <?php
    }

    

    public function generate_csv( $assocDataArray ) {

        if ( !empty( $assocDataArray ) ):

            $fp = fopen( 'php://output', 'w' );
            fputcsv( $fp, array_keys( reset($assocDataArray) ) );

            foreach ( $assocDataArray AS $values ):
                fputcsv( $fp, $values );
            endforeach;

            fclose( $fp );
        endif;

        exit();
    }

    public function downlaod_template_csv( $type, $template )
    {
        $date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

        if( $type == 'points' && $template == 'formatted' )
        {
            $logs = array(
                array(
                    'reference' =>  'logging_in',
                    'user_id'   =>  1,
                    'date'      =>  1633344172,
                    'points'    =>  '10',
                    'entry'     =>  'Points for logging in',
                ),
                array(
                    'reference' =>  'logging_in',
                    'user_id'   =>  2,
                    'date'      =>  1633344172,
                    'points'    =>  '10',
                    'entry'     =>  'Points for logging in',
                ),
                array(
                    'reference' =>  'logging_in',
                    'user_id'   =>  2,
                    'date'      =>  1633344172,
                    'points'    =>  '10',
                    'entry'     =>  'Points for logging in',
                ),
                array(
                    'reference' =>  'logging_in',
                    'user_id'   =>  1,
                    'date'      =>  1633344172,
                    'points'    =>  '10',
                    'entry'     =>  'Points for logging in',
                ),
            );
    
            $prep_logs= [];
    
            foreach ( $logs as $key => $user ) :
    
                $user_identification = $user['user_id'];
    
                $prep_logs[$key]['User (ID, username or email)'] = $user_identification;
    
                $prep_logs[$key]['Reference'] = $user['reference'];
    
                $prep_logs[$key]['Date'] = date_i18n( $date_format, $user['date'] );
    
                $prep_logs[$key]['Points'] = $user['points'];
    
                $prep_logs[$key]['Entry'] = $user['entry'];
    
            endforeach;
    
            return $this->generate_csv( $prep_logs );
        }

        if( $type == 'points' && $template == 'raw' )
        {
            $logs = array(
                array(
                    'id'        =>  1,
                    'ref'       =>  'registration',
                    'ref_id'    =>  1,
                    'user_id'   =>  1,
                    'creds'     =>  100,
                    'ctype'     =>  'mycred_default',
                    'time'      =>  '1633344167',
                    'entry'     =>  'Manual Entry by Tester',
                    'data'      =>  'a:1:{s:8:"ref_type";s:4:"user";}'
                ),
                array(
                    'id'        =>  2,
                    'ref'       =>  'site_visit',
                    'ref_id'    =>  1,
                    'user_id'   =>  1,
                    'creds'     =>  50,
                    'ctype'     =>  'mycred_default',
                    'time'      =>  '1633344167',
                    'entry'     =>  'Manual Entry by Tester',
                    'data'      =>  'a:1:{s:8:"ref_type";s:4:"user";}'
                ),
                array(
                    'id'        =>  3,
                    'ref'       =>  'logging_in',
                    'ref_id'    =>  1,
                    'user_id'   =>  1,
                    'creds'     =>  100,
                    'ctype'     =>  'mycred_default',
                    'time'      =>  '1633344167',
                    'entry'     =>  '%plural% for logging in',
                    'data'      =>  ''
                ),
                array(
                    'id'        =>  4,
                    'ref'       =>  'registration',
                    'ref_id'    =>  1,
                    'user_id'   =>  1,
                    'creds'     =>  100,
                    'ctype'     =>  'mycred_default',
                    'time'      =>  '1633344167',
                    'entry'     =>  'Manual Entry by Tester',
                    'data'      =>  'a:1:{s:8:"ref_type";s:4:"user";}'
                ),
                array(
                    'id'        =>  5,
                    'ref'       =>  'link_click',
                    'ref_id'    =>  1,
                    'user_id'   =>  1,
                    'creds'     =>  100,
                    'ctype'     =>  'mycred_default',
                    'time'      =>  '1633344167',
                    'entry'     =>  '%plural% for clicking on link to: %url%',
                    'data'      =>  'a:4:{s:8:"ref_type";s:4:"link";s:8:"link_url";s:20:"http://www.mycred.me";s:7:"link_id";s:13:"hswwwmycredme";s:10:"link_title";s:14:"View portfolio";}'
                ),
            );
    
            $prep_logs = array();
    
            foreach ( $logs as $key => $user ) :
    
                $user_identification = $user['user_id'];
    
                $prep_logs[$key]['id'] = $user['id'];
    
                $prep_logs[$key]['ref'] = $user['ref'];
    
                $prep_logs[$key]['ref_id'] = $user['ref_id'];
    
                $prep_logs[$key]['user_id'] = $user['user_id'];
    
                $prep_logs[$key]['creds'] = $user['creds'];

                $prep_logs[$key]['ctype'] = $user['ctype'];

                $prep_logs[$key]['time'] = $user['time'];

                $prep_logs[$key]['entry'] = $user['entry'];

                $prep_logs[$key]['data'] = $user['data'];
    
            endforeach;
    
            return $this->generate_csv( $prep_logs );
        }

        if( $type == 'badges' && $template == 'raw' )
        {
            $logs = array(
                array(
                    'user'  =>  1,
                    'badge' => '1, 2, 3'
                ),
                array(
                    'user'  =>  2,
                    'badge' => '1, 2'
                ),
                array(
                    'user'  =>  3,
                    'badge' => '2, 3'
                ),
                array(
                    'user'  =>  4,
                    'badge' => '3, 1'
                ),
                array(
                    'user'  =>  5,
                    'badge' => '1, 2'
                ),
            );
    
            $prep_raw = array();
    
            foreach ( $logs as $key => $user ) :
    
                $prep_raw[$key]['user'] = $user['user'];
    
                $prep_raw[$key]['badge'] = $user['badge'];
    
            endforeach;
    
            return $this->generate_csv( $prep_raw );
        }

        if( $type == 'ranks' && $template == 'raw' )
        {
            $logs = array(
                array(
                    'user'  =>  1,
                    'rank'  => '1, 2, 3'
                ),
                array(
                    'user'  =>  2,
                    'rank'   => '1, 2'
                ),
                array(
                    'user'  =>  3,
                    'rank'   => '2, 3'
                ),
                array(
                    'user'  =>  4,
                    'rank'  => '3, 4'
                ),
                array(
                    'user'  =>  5,
                    'rank'  => '1, 3'
                ),
            );
    
            $prep_raw = array();
    
            foreach ( $logs as $key => $user ) :
    
                $prep_raw[$key]['user'] = $user['user'];
    
                $prep_raw[$key]['rank'] = $user['rank'];
    
            endforeach;
    
            return $this->generate_csv( $prep_raw );
        }

    }

    public function export_csv( $type, $template, $user_field = 'id', $types = array( MYCRED_DEFAULT_TYPE_KEY ), $post_field = null )
    {
        if( $type == 'points' && $template == 'raw' )
        {
            $args = array(
                'number'    =>  -1,
                'order'     =>  'ASC',
                'ctype'     =>  array(
                    'ids'       =>  $types,
                    'compare'   =>  'IN'
                )
            );

            $logs  = new myCRED_Query_Log( $args );

            $prep_logs = array();
    
            foreach ( $logs->results as $key => $log ) :

                $log->user_id = $this->get_user_by( $user_field, $log->user_id );
    
                $user_identification = $log->user_id;
    
                $prep_logs[$key]['id'] = $log->id;
    
                $prep_logs[$key]['ref'] = $log->ref;
    
                $prep_logs[$key]['ref_id'] = $log->ref_id;
    
                $prep_logs[$key]['user_id'] = $log->user_id;
    
                $prep_logs[$key]['creds'] = $log->creds;

                $prep_logs[$key]['ctype'] = $log->ctype;

                $prep_logs[$key]['time'] = $log->time;

                $prep_logs[$key]['entry'] = $log->entry;

                $prep_logs[$key]['data'] = $log->data;
    
            endforeach;
    
            return $this->generate_csv( $prep_logs );
            
        }

        if( $type == 'points' && $template == 'formatted' )
        {
            $date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

            $args = array(
                'number'    =>  -1,
                'order'     =>  'ASC',
                'ctype'     =>  array(
                    'ids'       =>  $types,
                    'compare'   =>  'IN'
                )
            );

            $logs  = new myCRED_Query_Log( $args );

            $prep_logs = array();
    
            foreach ( $logs->results as $key => $log ) :

                $log->user_id = $this->get_user_by( $user_field, $log->user_id );
    
                $user_identification = $log->user_id;
    
                $prep_logs[$key]['User (ID, username or email)'] = $log->user_id;
    
                $prep_logs[$key]['Reference'] = ucwords( str_replace( array( '-', '_' ), ' ', $log->ref ) );
    
                $prep_logs[$key]['Date'] = date_i18n( $date_format, $log->time );
    
                $prep_logs[$key]['Points'] = $log->creds;

                $mycred = mycred( $log->ctype );

                $prep_logs[$key]['Entry'] = $mycred->parse_template_tags( $log->entry, $log );
    
            endforeach;
    
            return $this->generate_csv( $prep_logs );
            
        }

        if( $type == 'badges' && $template == 'raw' )
        {
            $user_ids = $this->get_all_user_ids();

            $prep_raw = array();

            foreach( $user_ids as $key => $user_id )
            {
                $user_has_badge = array();

                foreach( $types as $badge_id )
                {
                    if( mycred_user_has_badge( $user_id, $badge_id ) )
                    {
                        $prep_raw[$key]['user'] = $this->get_user_by( $user_field, $user_id );

                        $user_has_badge[] = $this->get_post_by( $post_field, $badge_id );
                    }   
                    else
                    {
                        continue;
                    }
                    $prep_raw[$key]['badge'] = implode( ', ', $user_has_badge );
                }
            }
            $this->generate_csv( $prep_raw );

            die;
        }

        //Raw ranks
        if( $type == 'ranks' && $template == 'raw' )
        {
            $user_ids = $this->get_all_user_ids();

            $prep_raw = array();

            foreach( $user_ids as $key => $user_id )
            {
                $user_has_rank = array();

                foreach( $types as $rank_id )
                {
                    
                    $rank = new myCRED_Rank( $rank_id );

                    if( $rank->user_has_rank( $user_id ) )
                    {
                        $prep_raw[$key]['user'] = $this->get_user_by( $user_field, $user_id );

                        $user_has_rank[] = $this->get_post_by( $post_field, $rank_id );
                    }   
                    else
                    {
                        continue;
                    }
                    $prep_raw[$key]['rank'] = implode( ', ', $user_has_rank );
                }
            }
            $this->generate_csv( $prep_raw );

            die;
        }
        
        //Export Setup
        if( $type == 'setup' && $template == 'raw' )
        {
            $this->export_setup( $post_field );
        }
    }

    public function import_csv( $file_path, $type, $import_format_type = '' )
    {
        $row = 1;

        if( ( $handle = fopen( $file_path, 'r' ) ) !== false ) 
        {
            $rows_affected = 0;

            while( ( $data = fgetcsv( $handle, 1000, ',' ) ) !== false ) 
            {
                
                $num = count( $data );
                
                //Start adding
                if( $type == 'points' )
                {
                    //If header is not of points
                    if( $row == 1 )
                    {
                        $row++;
                        if( 
                            preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[0] ) != 'id'
                            || 
                            preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[1] ) != 'ref' 
                            || 
                            preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[2] ) != 'ref_id' 
                            || 
                            preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[3] ) != 'user_id' 
                            ||
                            preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[4] ) != 'creds' 
                            || 
                            preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[5] ) != 'ctype' 
                            || 
                            preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[6] ) != 'time' 
                            || 
                            preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[7] ) != 'entry' 
                            || 
                            preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[8] ) != 'data' 
                        )
                        {
                            wp_send_json( "{$rows_affected} rows affected.", 200 );
                        }
                        
                        continue;
                    }


                    //Add Creds
                    $id = $data[0];
                    $ref = $data[1];
                    $ref_id = $data[2];
                    $user_id = '';

                    //Get User Id
                    $user_id = $this->get_user_id( $data[3] );

                    $creds = $data[4];
                    $ctype = $data[5];
                    $time = $data[6];
                    $entry = $data[7];
                    $data = $data[8];

                    $add_creds = mycred_add(
                        $ref,
                        $user_id,
                        $creds,
                        $entry,
                        $ref_id,
                        $data,
                        $ctype
                    );

                    if( $add_creds )
                        $rows_affected++;
                }

                //Start Assigning Badges
                if( $type == 'badges' )
                {
                    //If header is not of Badges type
                    if( $row == 1 )
                    {
                        $row++;
                        if( preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[0] ) != 'user' || preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[1] ) != 'badge' )
                            wp_send_json( "{$rows_affected} rows affected.", 200 );
                        
                        continue;
                    }

                    $user_id = $this->get_user_id( $data[0] );

                    $badge_ids = explode( ', ', $data[1] );

                    foreach( $badge_ids as $badge )
                    {
                        $badge_id = $this->get_post_id_by( $import_format_type, $badge, MYCRED_BADGE_KEY );

                        $assigned_badge = mycred_assign_badge_to_user( $user_id, $badge_id );

                        if( $assigned_badge )
                            $rows_affected++;
                    }
                }

                //Start Assiging Ranks
                if( $type == 'ranks' )
                {
                    //If header is not of rank type
                    if( $row == 1 )
                    {
                        $row++;
                        if( preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[0] ) != 'user' || preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $data[1] ) != 'rank' )
                            wp_send_json( "{$rows_affected} rows affected.", 200 );
                        
                        continue;
                    }

                    $user_id = $this->get_user_id( $data[0] );

                    $rank_ids = explode( ', ', $data[1] );

                    foreach( $rank_ids as $rank_id )
                    {
                        $rank_id = $this->get_post_id_by( $import_format_type, $rank_id, MYCRED_RANK_KEY );

                        $rank = new myCRED_Rank( $rank_id );

                        $assigned_rank = $rank->assign( $user_id );

                        if( $assigned_rank )
                            $rows_affected++;
                    }
                }

            }

            fclose( $handle );

            wp_send_json( "{$rows_affected} rows affected.", 200 );
        }
    }

    public function import_export()
    {

        check_ajax_referer( 'mycred-tools', 'token' );

        $current_user_id = get_current_user_id();
        $mycred = mycred();

        if ( ! $mycred->user_is_point_admin( $current_user_id ) ) {

            wp_send_json( array( 'success', 'accessDenied' ) );
            wp_die();

        }

        if( isset( $_POST['action'] ) && $_POST['action'] == 'mycred-tools-import-export' )
        {
            //Export Raw points 
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'points' && isset( $_POST['request'] ) && $_POST['request'] == 'export' )
            {

                $point_types = isset( $_POST['types'] ) ? sanitize_text_field( wp_unslash( $_POST['types'] ) ) : json_encode( array( MYCRED_DEFAULT_TYPE_KEY ) );
                $point_types = json_decode( $point_types );

                $point_types = mycred_sanitize_array( $point_types );
                
                $user_field = isset( $_POST['user_field'] ) ? sanitize_text_field( wp_unslash( $_POST['user_field'] ) ) : 'id';
                $template   = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'raw';
                
                return $this->export_csv( 'points', $template, $user_field, $point_types );

                die;
            }
            
            //Import Points
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'points' && $_POST['request'] == 'import' && isset( $_FILES ) )
            {

                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $file_path = isset( $_FILES['_file']['tmp_name'] ) ? sanitize_text_field( $_FILES['_file']['tmp_name'] ) : '';
    
                $this->import_csv( $file_path, 'points' );
            }

            //Formatted points template
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'points' && $_POST['template'] == 'formatted' )
            {
                return $this->downlaod_template_csv( 'points', 'formatted' );

                die;
            }

            //Raw points template
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'points' )
            {
                return $this->downlaod_template_csv( 'points', 'raw' );

                die;
            }

            //Badges
            //Export Raw Badges
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'badges' && isset( $_POST['request'] ) && $_POST['request'] == 'export' )
            {
                $template = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'raw';

                $user_field = isset( $_POST['user_field'] ) ? sanitize_text_field( wp_unslash( $_POST['user_field'] ) ) : 'id';

                $post_field = isset( $_POST['post_field'] ) ? sanitize_text_field( wp_unslash( $_POST['post_field'] ) ) : 'id';

                $badges = isset( $_POST['types'] ) ? sanitize_text_field( wp_unslash( $_POST['types'] ) ) : json_encode( array() );

                $badges = json_decode( $badges );

                $badges = mycred_sanitize_array( $badges );

                return $this->export_csv( 'badges', $template, $user_field, $badges, $post_field );

                die;
            }

            //Import Badges
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'badges' && $_POST['request'] == 'import' && isset( $_FILES ) )
            {

                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $file_path = isset( $_FILES['_file']['tmp_name'] ) ? sanitize_text_field( $_FILES['_file']['tmp_name'] ) : '';

                $import_format_type = isset( $_POST['import_format_type'] ) ? sanitize_text_field( wp_unslash( $_POST['import_format_type'] ) ) : 'id';

                $this->import_csv( $file_path, 'badges',  $import_format_type  );
            }

            //Raw Badges template
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'badges' && $_POST['template'] == 'raw' )
            {

                return $this->downlaod_template_csv( 'badges', 'raw' );

                die;
            }

            //Ranks
            //Import Ranks
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'ranks' && $_POST['request'] == 'import' && isset( $_FILES ) )
            {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $file_path = isset( $_FILES['_file']['tmp_name'] ) ? sanitize_text_field( $_FILES['_file']['tmp_name'] ) : '';

                $import_format_type = isset( $_POST['import_format_type'] ) ? sanitize_text_field( wp_unslash( $_POST['import_format_type'] ) ) : 'id';

                $this->import_csv( $file_path, 'ranks',  $import_format_type  );
            }

            //Export Raw Ranks
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'ranks' && $_POST['request'] == 'export' )
            {

                $template = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'raw';

                $user_field = isset( $_POST['user_field'] ) ? sanitize_text_field( wp_unslash( $_POST['user_field'] ) ) : 'id';

                $post_field = isset( $_POST['post_field'] ) ? sanitize_text_field( wp_unslash( $_POST['post_field'] ) ) : 'id';

                $ranks = isset( $_POST['types'] ) ? sanitize_text_field( wp_unslash( $_POST['types'] ) ) : json_encode( array() );

                $ranks = json_decode( $ranks );

                $ranks = mycred_sanitize_array( $ranks );

                return $this->export_csv( 'ranks', $template, $user_field, $ranks, $post_field );

                die;
            }

            //Raw Ranks Template
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'ranks' && $_POST['template'] == 'raw' )
            {

                return $this->downlaod_template_csv( 'ranks', 'raw' );

                die;
            }

            //Setup
            //Export Setup
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'setup' && ( isset( $_POST['template'] ) && $_POST['template'] == 'raw' ) )
            {
                
                $setup_types = isset( $_POST['setup_types'] ) ? mycred_sanitize_array( wp_unslash( $_POST['setup_types'] ) ) : array();
                
                $template    = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'raw';

                return $this->export_csv( 'setup', $template, '', '', $setup_types );
            }

            //Setup
            //Import Setup
            if( isset( $_POST['request_tab'] ) && $_POST['request_tab'] == 'setup' && $_POST['request'] == 'import' )
            {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $file_path = isset( $_FILES['_file']['tmp_name'] ) ? sanitize_text_field( $_FILES['_file']['tmp_name'] ) : '';

                $this->import_setup_json( $file_path );
            }
        }
    }

    public function get_user_id( $user )
    {   
        if( filter_var( $user, FILTER_VALIDATE_EMAIL ) )
            return get_user_by( 'email', $user )->ID;

        if( is_string( $user ) && !is_numeric( $user ) )
            return get_user_by( 'login', $user )->ID;

        if( is_numeric( $user ) )
            return $user;
    }

    public function get_user_by( $user_field, $user_id )
    {
        if( $user_field == 'id' )
            return $user_id = $user_id;

        if( $user_field == 'user_name' )
            return $user_id = get_userdata( $user_id )->user_login;

        if( $user_field == 'email' )
            return $user_id = get_userdata( $user_id )->user_email;
    }

    public function get_all_user_ids()
    {
        $user_ids = array(); 
        
        foreach( get_users() as $user  )
            $user_ids[] = $user->ID;

        return $user_ids;
    }

    public function get_post_by( $post_field, $post_id )
    {
        if( $post_field == 'id' )
            return $post_id;
        
        if( $post_field == 'title' )
            return get_the_title( $post_id );

        if( $post_field == 'slug' )
            return get_post_field( 'post_name', $post_id );
    }

    public function get_post_id_by( $import_format_type, $post_field, $post_type = '' )
    {
        if( $import_format_type == 'id' )
            return $post_field;

        if( $import_format_type == 'slug' )
        {
            $args = array(
                'name'          =>  $post_field,
                'post_status'   =>  'publish',
                'numberposts'   =>  1,
                'post_type'     =>  $post_type
              );

              return get_posts( $args )[0]->ID;
        }

        if( $import_format_type == 'title' )
        {
            $post = get_page_by_title( $post_field, OBJECT, $post_type );

            return $post->ID;
        }
    }

    public function get_badge_categories()
    {
        $args = array(
            'taxonomy'      =>  MYCRED_BADGE_CATEGORY,
            'orderby'       =>  'name',
            'field'         =>  'name',
            'order'         =>  'ASC',
            'hide_empty'    =>  false
        );

        return get_categories( $args );
    }

    public function get_uncat_badge_ids()
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT SQL_CALC_FOUND_ROWS wp_posts.ID FROM {$wpdb->posts} as wp_posts  WHERE 1=1  AND ( 
            wp_posts.ID NOT IN (
                SELECT object_id
                    FROM $wpdb->term_relationships
                )
            ) AND wp_posts.post_type = 'mycred_badge' AND (wp_posts.post_status = 'publish' OR wp_posts.post_status = 'future' OR wp_posts.post_status = 'draft' OR wp_posts.post_status = 'pending' OR wp_posts.post_status = 'private') GROUP BY wp_posts.ID ORDER BY wp_posts.post_date DESC LIMIT 0, 10",
            ARRAY_A
        );
    }

    public function insert_attachment_from_url( $url, $parent_post_id = null )
    {

        if( !class_exists( 'WP_Http' ) )
            include_once( ABSPATH . WPINC . '/class-http.php' );
    
        $http = new WP_Http();

        $response = $http->request( $url );

        //If Image not Found/ Just return empty string
        if( property_exists( $response, 'errors' ) )
            return '';

        if( $response['response']['code'] != 200 ) {
            return false;
        }

        $upload = wp_upload_bits( basename($url), null, $response['body'] );
        if( !empty( $upload['error'] ) ) {
            return false;
        }
    
        $file_path = $upload['file'];
        $file_name = basename( $file_path );
        $file_type = wp_check_filetype( $file_name, null );
        $attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
        $wp_upload_dir = wp_upload_dir();
    
        $post_info = array(
            'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
            'post_mime_type' => $file_type['type'],
            'post_title'     => $attachment_title,
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
    
        // Create the attachment
        $attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );
    
        // Include image.php
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
    
        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
    
        // Assign metadata to attachment
        wp_update_attachment_metadata( $attach_id,  $attach_data );
    
        return $attach_id;
    
    }
}
endif;


$mycred_tools_import_export = new myCRED_Tools_Import_Export();