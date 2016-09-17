<?php /* Template Name: Plantilla movilefunction */
global $wpdb;
//********************************************************************
//
// Propiedad intelectual de Gonzalo Cea Suazo.
// Ingeniero en Computación e Informática.
// Diseñado y creado para Aplicación Deemelo.
// Cualquier copia del código, estará sujeta a derechos de autor.
//
//*********************************************************************
$op=$_GET['op'];
$posts_per_page = 20;
$largeImagen = 'large';
$thumbnailImagen = 'medium';
//echo $site_title = get_bloginfo( 'template_url' );
//$thumbnailImagen = 'thumbnail';

function array_flatten($arr) {
    $arr = array_values($arr);
    while (list($k,$v)=each($arr)) {
        if (is_array($v)) {
            array_splice($arr,$k,1,$v);
            next($arr);
        }
    }
    return $arr;
}


// token auth validation by @gertfindel
$current_user_id = null;
function get_user_id_by_token($token) {
	$users = get_users(array('meta_key' => 'token', 'meta_value' => $token));
	if(count($users)>0){ return $users[0]->ID; }
	return null; 
}

function not_authenticated() {
	die (json_encode( array( "status"=>"error", "desc"=>"not authenticated" ) ));
}

function update_token_by_user_id($uid) {
	$random_password = wp_generate_password( $length=42, $include_standard_special_chars=false );
	update_user_meta( $uid, 'token', $random_password );
	return $random_password;
}

function fb_friends_by_fbtoken($access_token) {
      require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
      global $wpdb;
      $fql_query_url = 'https://graph.facebook.com/'
                        . 'fql?q=SELECT+uid2+FROM+friend+WHERE+uid1=me()'
                        . '&access_token=' . $access_token;
      $fql_query_result = file_get_contents($fql_query_url);
      $fql_query_obj = json_decode($fql_query_result, true);
      $result = array_flatten($fql_query_obj["data"]);
      $fbids = array_flatten($wpdb->get_results("select u.facebook_id from wp_users as u where  u.facebook_id != '0'", "ARRAY_N" ));
      //$fbids = get_users('meta_key=facebook_id&meta_value=1&meta_compare=>');
      return array_intersect($result, $fbids);
}

if($op != "iniciarsesion" && $op != "listcat" && $op != "registro" && ($op == "getinfoperfil" && isset($_GET['fb_token'])) == false ) {
	if(empty($_GET['token']) || !isset($_GET['token'])) { not_authenticated(); }
	$current_user_id = get_user_id_by_token($_GET['token']);
	if(is_int($current_user_id)==false) { not_authenticated(); }
}

switch ($op) {
	case "getinfoperfil": $email=$_GET['email'];
		if($email == 'undefined' || empty($email)):
			echo json_encode( array( "id"=>' ',"nombre"=>' ',"url"=>' ',"descripcion"=>' ' ) );
		else:
			if ( email_exists( $email ) ): $user_info = get_userdata( email_exists( $email ) );
				if ( !empty( $_GET["img"] ) ){ update_user_meta( $user_info->ID, 'ruta_thumbnail', $_GET["img"] ); }
				$ruta_thumbnail = get_user_meta( $user_info->ID, 'ruta_thumbnail', true );
				if ( user_can($user_info->ID, 'administrator') || user_can($user_info->ID, 'subscriber') ) { $role = 'usuario'; 
					if(!isset($_GET['token'])) {$token = update_token_by_user_id($user_info->ID);} else {$token = "";}
					$info = array( "token"=>$token, "tipo_usuario"=>$role,"id"=>$user_info->ID,"username"=>$user_info->user_login,"correo"=>$user_info->user_email,"nombre"=>$user_info->display_name,"url"=>$user_info->user_url,"descripcion"=>$user_info->description,"ruta_thumbnail"=>$ruta_thumbnail ); }
				elseif( user_can($user_info->ID, 'tienda') ){ $role = 'tienda'; 
					$latitud = get_user_meta( $user_info->ID, 'latitud', true );
					$longitud = get_user_meta( $user_info->ID, 'longitud', true );
					$direccion = get_user_meta( $user_info->ID, 'direccion', true );
					$telefono = get_user_meta( $user_info->ID, 'user_telefono', true);
					if(!isset($_GET['token'])) {$token = update_token_by_user_id($user_info->ID);} else {$token = "";}
					$info = array("token"=>$token, "tipo_usuario"=>$role,"latitud"=>$latitud,"longitud"=>$longitud,"direccion"=>$direccion,"ID"=>$user_info->ID,"username"=>$user_info->user_login,"email_tienda"=>$user_info->user_email,"display_name"=>$user_info->display_name,"url"=>$user_info->user_url, "telefono"=>$telefono,"descripcion"=>$user_info->description,"ruta_thumbnail"=>$ruta_thumbnail ); }
				echo json_encode( $info );
			else:
				if( $_GET['facebook_id'] != 'undefined' && $_GET['nombres'] != 'undefined' && $_GET['url'] != 'undefined' && $_GET['img'] != 'undefined' && $_GET['sexo'] != 'undefined' ):
					$facebook_id = $_GET['facebook_id']; $nombres = $_GET["nombres"]; $desc = $_GET["desc"]; $nicename = $_GET["nombres"]; $url = $_GET["url"]; $imagen = $_GET["img"]; $sexo = $_GET["sexo"]; $fb_token = $_GET['fb_token'];
$nombres = iconv('UTF-8', 'ASCII//TRANSLIT', $nombres);
			    	$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
			    	// inserta en la tabla wp_users
					$user_id = wp_insert_user( array ('user_login' => $email, 'user_pass' => $random_password, 'user_nicename' => $nicename, 'user_email' => $email,'user_url' => $url, 'display_name' => $nombres, 'nickname' => $_GET['nombre'] ) ) ;
		 			if ($user_id):
		 				$face = $wpdb->update( 'wp_users', array( 'facebook_id' => $facebook_id ), 
											array( 'ID' => $user_id ), array( '%d' ), 
											array( '%d') );
			 			// agrega campos a la tabla wp_usermeta
						add_user_meta( $user_id, 'sexo', $sexo);
							add_user_meta( $user_id, 'fb_token', $fb_token);
							$token = update_token_by_user_id($user_id);
						add_user_meta(  $user_id, 'ruta_thumbnail', $imagen);
						// actualiza campos de la tabla wp_usermeta
						update_user_meta( $user_id, 'first_name', $nombres);
						update_user_meta( $user_id, 'facebook_id', $facebook_id);

						if( empty($desc) ):	update_user_meta( $user_id, 'description', ' ');
					        else:
					    	  if ($desc == 0) { $desc == ' '; update_user_meta( $user_id, 'description', ' '); }
					    	  else{ update_user_meta( $user_id, 'description', $desc);}
					        endif;
	   				        wp_new_user_notification( $user_id, $random_password );
					        $data =  array("token"=>$token, "correo"=>$email, "username"=>$email,"tipo_usuario" => "usuario", "id"=>$user_id, "nombre"=>$nombres,"url"=>$url,"descripcion"=>$desc,"ruta_thumbnail"=>$imagen );
					        echo json_encode($data);
                                                // notificacion tipo 22 (holi tu amigo de facebook tb esta en deemelo)
                                           	$fbids = fb_friends_by_fbtoken($fb_token);
                                                $user_info = get_userdata($user_id);
                                                $author = $user_info->display_name;
                                                $ruta_thumbnail = get_user_meta( $user_id, 'ruta_thumbnail', true );
                                                foreach($fbids as $afbid):
                                                    $thid = $wpdb->get_var( "SELECT id FROM wp_users where facebook_id = '$afbid'" );
                                                    $wpdb->query("INSERT INTO notificaciones (user_id,type,target_id,caller_name,caller_thumbnail,target_email) VALUES ('$thid','22','$user_id','$author','$ruta_thumbnail','$email')");
                                   		endforeach;		   
 
				   	else: echo json_encode( array( "status"=>"error", "desc"=>"ocurrio un error al ingresar el usuario." ) );
					endif;
				else: echo json_encode( array( "status"=>"error", "desc"=>"ocurrio un error con la informacion de facebook." ) );
				endif;
			endif;
		endif;
    	break;

    case "registro": $password=$_GET['pass']; $email=$_GET['email']; $nombres=$_GET['nombre']; $username=$_GET['email'];
	    	$user_id = wp_create_user( $username, $password, $email );
	    	if ( is_wp_error($user_id) ): echo json_encode( array( "status"=> $user_id->get_error_code() ) );
	    	else:
	    		add_user_meta( $user_id, 'sexo', $sexo);
	    		update_user_meta( $user_id, 'first_name', $nombres);
				update_user_meta( $user_id, 'display_name', $nombres);
				update_user_meta( $user_id, 'user_nicename', $nombres);
				update_user_meta( $user_id, 'nickname', $nombres);
				$token = update_token_by_user_id($user_id);
			    $user_info = get_userdata( $user_id );
			    wp_new_user_notification( $user_id, $password );
			    echo json_encode( array( "id"=>$user_info->ID,"username"=>$user_info->user_login,"correo"=>$user_info->user_email,"nombre"=>$user_info->display_name, "token"=>$token ) );
	    	endif;
    	break;

    case "updateperfil": $email=$_GET['email'];
    	require_once( ABSPATH . WPINC . '/registration.php');
	    if ( email_exists( $email ) ): $id=email_exists($email);
	    	$userdata = array('ID' => $id, 'user_login' => $_GET['username'], 'first_name' => $_GET['nombre'], 'user_url' => $_GET['url'], 'display_name' => $_GET['nombre'], 'user_nicename' => $_GET['nombre'], 'nickname' => $_GET['nombre'] );
	    	$userid = wp_update_user( $userdata );
	    	update_user_meta( $userid, 'sexo', $_GET['sexo'] );
	    	$user_info = get_userdata( $userid );
            echo json_encode( array( "username"=>$user_info->user_login,"nombre"=>$user_info->display_name,"url"=>$user_info->user_url, "correo"=>$user_info->user_email ) );
        else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
    	break;

    case "updatepassword": $email=$_GET['email']; $password=$_GET['newpass'];
    	require_once( ABSPATH . WPINC . '/registration.php');
	    if ( email_exists( $email ) ): $user_id=email_exists($email);
	    	$userdata = array('ID' => $user_id, 'user_pass' => $password );
	    	$userid = wp_update_user( $userdata );
	    	if ($userid): echo json_encode( array( "status"=> 'ok' ) );
	    	else: echo json_encode( array( "status"=> 'error' ) );
	    	endif;
        else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
    	break;

    case "iniciarsesion": $username=$_GET['username']; $plain_password=$_GET['pass'];
	    	$creds = array();
			$creds['user_login'] = $username;
			$creds['user_password'] = $plain_password;
			$creds['remember'] = true;
			$user = wp_signon( $creds, false );
			if ( is_wp_error($user) ): echo json_encode( array( "status"=> $user->get_error_code() ) );
			else:
				$user_info = get_userdata( $user->ID );
				$ruta_thumbnail = get_user_meta( $user_info->ID, 'ruta_thumbnail', true );
				if ( user_can($user_info->ID, 'administrator') || user_can($user_info->ID, 'subscriber') ) { $role = 'usuario'; 
					$token = update_token_by_user_id($user_info->ID);

					$info = array( "token"=>$token, "tipo_usuario"=>$role,"id"=>$user_info->ID,"username"=>$user_info->user_login,"correo"=>$user_info->user_email,"nombre"=>$user_info->display_name,"url"=>$user_info->user_url,"descripcion"=>$user_info->description,"ruta_thumbnail"=>$ruta_thumbnail ); }
				elseif( user_can($user_info->ID, 'tienda') ){ $role = 'tienda'; 
					$latitud = get_user_meta( $user_info->ID, 'latitud', true );
					$longitud = get_user_meta( $user_info->ID, 'longitud', true );
					$direccion = get_user_meta( $user_info->ID, 'direccion', true ); 
					$info = array( "tipo_usuario"=>$role,"latitud"=>$latitud,"longitud"=>$longitud,"direccion"=>$direccion,"ID"=>$user_info->ID,"username"=>$user_info->user_login,"email_tienda"=>$user_info->user_email,"display_name"=>$user_info->display_name,"url"=>$user_info->user_url,"descripcion"=>$user_info->description,"ruta_thumbnail"=>$ruta_thumbnail ); }
            	echo json_encode( $info );
			endif;
    	break;

	case "count": $email=$_GET['email'];
	    if ( email_exists( $email ) ): $id=email_exists($email);
            $like_count = $wpdb->get_var( "SELECT count(s.megusta) FROM seguidor_images as s where s.seguidor_id= '$id' and   s.megusta = 1" );
            $want_count = $wpdb->get_var( "SELECT count(s.want) FROM seguidor_images as s where s.seguidor_id= '$id' and   s.want = 1" );
            $siguiendo_count = $wpdb->get_var( "SELECT count(u.seguidor_id) FROM usuario_seguidor as u where u.usuario_id= '$id' " );
            $mesiguen_count = $wpdb->get_var( "SELECT count(u.usuario_id) FROM usuario_seguidor as u where u.seguidor_id= '$id' " );
            echo json_encode( array( "likes"=>$like_count,"want"=>$want_count,"siguiendo"=>$siguiendo_count, "seguidores"=>$mesiguen_count,"post_user"=>count_user_posts($id) ) );
        else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
    	break;

    case "getmegusta": $email=$_GET['email'];
    	if ( email_exists( $email ) ):
    		global $post;
			require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
			if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }

			$seguidor_id = email_exists($email);
            $megusta = $wpdb->get_results( "SELECT images_id FROM seguidor_images WHERE seguidor_id= $seguidor_id AND megusta = 1 LIMIT $offset,$posts_per_page" );
            foreach ( $megusta as $me ):
				$id_author = get_post($me->images_id);
				$user_info = get_userdata( $id_author->post_author );
				$ruta_thumbnail = get_user_meta( $id_author->post_author, 'ruta_thumbnail', true );
				$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id( $me->images_id ), $thumbnailImagen );
				$user = get_user_by('id', $id_author->post_author);
				$image[] = array( "post_id"=>$me->images_id,"author"=>$user->display_name, "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend"=>$user_info->user_email );
			endforeach;
            echo json_encode( $image );
        else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
    	break;

    case "getloquiero": $email=$_GET['email'];
    	if ( email_exists( $email ) ):
    		global $post; require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
			if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
			$seguidor_id = email_exists($email);
            $megusta = $wpdb->get_results( "SELECT images_id FROM seguidor_images WHERE seguidor_id = $seguidor_id AND want = 1 LIMIT $offset,$posts_per_page" );
            foreach ( $megusta as $me ):
				$id_author = get_post($me->images_id);
				if ($id_author):
					$ruta_thumbnail = get_user_meta( $id_author->post_author, 'ruta_thumbnail', true );
					$user_info = get_userdata( $id_author->post_author );
					$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id( $me->images_id ), $thumbnailImagen );
					$user = get_user_by('id', $id_author->post_author);
					$image[] = array( "id"=>$me->images_id,"author"=>$user->display_name, "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend"=>$user_info->user_email );
				endif;
			endforeach;
            echo json_encode( $image );
        else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
    	break;

    case "megustaloquiero": $email=$_GET['email']; $post_id=$_GET['post_id'];
    	if ( email_exists( $email ) ):
    		$seguidor_id = email_exists($email);
            $megusta = $wpdb->get_var( "SELECT megusta FROM seguidor_images WHERE seguidor_id= '$seguidor_id' AND  images_id = '$post_id'" );
            $want = $wpdb->get_var( "SELECT want FROM seguidor_images WHERE seguidor_id= '$seguidor_id' AND  images_id = '$post_id'" );
            if ($megusta == 0 || $megusta == '' || $megusta == null): $megusta = 0; endif;
            if ($want == 0 || $want == '' || $want == null): $want = 0; endif;
            echo json_encode( array( "megusta"=>$megusta,"want"=>$want ) );
        else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
    	break;

    case "getpostlistimageamigos": $email=$_GET['email'];
		global $post;
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
	    if ( email_exists( $email ) ):
	    	if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
	    	$id = email_exists($email);
	    	$amigo = '';
	    	$sigo = $wpdb->get_results( "SELECT u.ID as ID FROM usuario_seguidor as us, wp_users as u where u.ID = us.seguidor_id and us.usuario_id = '$id'" );
	    	foreach( $sigo as $si ): $amigo .= ''.$si->ID.','; endforeach;
		    	$author_query = array('posts_per_page' => $posts_per_page, 'author' => $amigo , 'offset' => $offset );
				$the_query = new WP_Query( $author_query );
				while ( $the_query->have_posts() ) :
					$the_query->the_post();
					$id = get_the_author_meta( 'ID' );
					$user_info = get_userdata( $id );
	    			$ruta_thumbnail = get_user_meta( $id, 'ruta_thumbnail', true ); 
					$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumbnailImagen ); 
					$imge[] = array( "id"=>$post->ID,"author"=>get_the_author(), "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend" =>$user_info->user_email );
				endwhile;
			echo json_encode($imge);
			
		else: echo "That E-mail doesn't belong to any registered users on this site";
	    endif;
    	break;

    case "quienessigo": $email=$_GET['email'];
	    if ( email_exists( $email ) ):
	    	$id = email_exists( $email );
	    	$sigo = $wpdb->get_results( "SELECT u.facebook_id as facebook_id, u.ID as ID FROM usuario_seguidor as us, wp_users as u where u.ID = us.seguidor_id and us.usuario_id = '$id'");
	    	if($sigo):
		    	foreach( $sigo as $si ):
		    		$user_info = get_userdata($si->ID);
		    		$ruta_thumbnail = get_user_meta( $si->ID, 'ruta_thumbnail', true );
		    		$arreglo[] = array( "facebook_id"=>$si->facebook_id,"nombre"=>$user_info->user_firstname,"id"=>$si->ID, "email"=>$user_info->user_email,"ruta_thumbnail"=>$ruta_thumbnail );
	            endforeach;
	        else: $arreglo[] = array( "status"=> 'vacio' );
	        endif;
            echo json_encode( $arreglo );
        else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
    	break;

    case "sigoamigo": $email=$_GET['email']; $emailfriend=$_GET['emailfriend'];
	    if ( email_exists( $email ) ):
	    	$id = email_exists( $email );
	    	$idfriend = email_exists( $emailfriend );
	    	$s_id = $wpdb->get_var( "SELECT facebook_id FROM wp_users where ID = '$idfriend'" );
	    	$sigo = $wpdb->get_results( "SELECT u.facebook_id as facebook_id FROM usuario_seguidor as us, wp_users as u where u.ID = us.seguidor_id and us.usuario_id = '$id' and us.seguidor_id = '$idfriend'" );
	    	if ($sigo): echo json_encode( $sigo );
	    	else: echo json_encode( array('status' => 'error','facebook_id' => $s_id ) );
            endif;
        else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
    	break;

    case "quienesmesiguen": $email=$_GET['email'];
	    if ( email_exists( $email ) ): 
	    	$id=email_exists($email);
            $mesiguen = $wpdb->get_results( "SELECT u.facebook_id as facebook_id, u.ID as ID FROM usuario_seguidor as us, wp_users as u where u.ID = us.usuario_id and   us.seguidor_id = '$id'" );
            if($mesiguen):
	            foreach( $mesiguen as $me ):
		    		$user_info = get_userdata($me->ID);
		    		$ruta_thumbnail = get_user_meta( $me->ID, 'ruta_thumbnail', true );
		    		$arreglo[] = array( "facebook_id"=>$me->facebook_id,"nombre"=>$user_info->user_firstname,"id"=>$me->ID, "email"=>$user_info->user_email,"ruta_thumbnail"=>$ruta_thumbnail );
	            endforeach;
	        else: $arreglo[] = array( "status"=> 'vacio' );
	        endif;
            echo json_encode( $arreglo );
        else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
    	break;

    case "megusta": $email=$_GET['email']; $post_id=$_GET['post_id'];
    	if ( email_exists( $email ) ):
    		$seguidor_id = email_exists($email);
    		$mylink = $wpdb->get_row("SELECT * FROM seguidor_images WHERE seguidor_id = $seguidor_id AND images_id = $post_id");
    		if ( $mylink ): 
    			$idmegusta = $wpdb->update( 
											'seguidor_images', 
											array( 
												'megusta' => 1	// integer (number) 
											), 
											array( 'seguidor_id' => $seguidor_id , 'images_id' => $post_id ), 
											array( 
												'%d'	// value1
											), 
											array( '%d', '%d') 
										);
    			if ( $idmegusta ): echo json_encode( array( "status"=>"ok" ) );
				else: echo json_encode( array( "status"=> "error update" ) );
				endif;
			else:
				$idmegusta = $wpdb->query( "INSERT INTO seguidor_images (seguidor_id, images_id, megusta) VALUES ($seguidor_id, $post_id, 1)" );
    			if ( $idmegusta ): echo json_encode( array( "status"=>"ok" ) );
				else: echo json_encode( array( "status"=> "error insert" ) );
				endif;
			endif;
    	else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
		break;

	case "nomegusta": $email=$_GET['email']; $post_id=$_GET['post_id'];
    	if ( email_exists( $email ) ):
    		$seguidor_id = email_exists($email);
    		$mylink = $wpdb->get_row("SELECT * FROM seguidor_images WHERE seguidor_id = $seguidor_id AND images_id = $post_id");
    		if ( $mylink ):
    			if ( $mylink->want == 0 ):
    				$idnomegusta = $wpdb->query( "DELETE FROM seguidor_images WHERE seguidor_id=$seguidor_id AND images_id=$post_id" );
    				if ( $idnomegusta ): echo json_encode( array( "status"=>"ok" ) );
    				else: echo json_encode( array( "status"=>"error al eliminar" ) );
    				endif;
    			else:
	    			$idnomegusta = $wpdb->update( 
												'seguidor_images', 
												array( 
													'like' => 0	// integer (number) 
												), 
												array( 'seguidor_id' => $seguidor_id , 'images_id' => $post_id), 
												array( 
													'%d'	// value1
												), 
												array( '%d', '%d') 
											);
	    			if ( $idnomegusta ): echo json_encode( array( "status"=>"ok" ) );
					else: echo json_encode( array( "status"=> "error" ) );
					endif;
				endif;
			else: echo json_encode( array( "status"=> "error, no existe" ) );
			endif;
    	else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
		break;

	case "loquiero": $email=$_GET['email']; $post_id=$_GET['post_id'];
    	if ( email_exists( $email ) ):
    		$seguidor_id = email_exists($email);
    		$mylink = $wpdb->get_row("SELECT * FROM seguidor_images WHERE seguidor_id = $seguidor_id AND images_id = $post_id");
    		if ( $mylink ): 
    			$idmegusta = $wpdb->update( 
											'seguidor_images', 
											array( 
												'want' => 1	// integer (number) 
											), 
											array( 'seguidor_id' => $seguidor_id , 'images_id' => $post_id), 
											array( 
												'%d'	// value1
											), 
											array( '%d', '%d') 
										);
    			if ( $idmegusta ): echo json_encode( array( "status"=>"ok" ) );
				else: echo json_encode( array( "status"=> "error update" ) ); break;
				endif;
		else:
				$idmegusta = $wpdb->query( "INSERT INTO seguidor_images (seguidor_id, images_id, want) VALUES ($seguidor_id, $post_id, 1)" );
    			if ( $idmegusta ): echo json_encode( array( "status"=>"ok" ) );
				else: echo json_encode( array( "status"=> "error insert" ) ); break;
				endif;
		endif;
              
		// notificacion tipo 11 (alguien quiere tu prenda)
		global $post;
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
		$post_info = get_post($post_id);
		$owner = get_userdata( $post_info->post_author );
		$user_info = get_userdata( $seguidor_id );
		$author = $user_info->display_name;
		$ruta_thumbnail = get_user_meta( $seguidor_id, 'ruta_thumbnail', true );
		$wpdb->query("INSERT INTO notificaciones (user_id,type,target_id, caller_name, caller_thumbnail) VALUES ('$owner->ID','11', '$post_id','$author', '$ruta_thumbnail')");
  
    	else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
		break;

	case "noloquiero": $email=$_GET['email']; $post_id=$_GET['post_id'];
    	if ( email_exists( $email ) ):
    		$seguidor_id = email_exists($email);
    		$mylink = $wpdb->get_row("SELECT * FROM seguidor_images WHERE seguidor_id = $seguidor_id AND images_id = $post_id");
    		if ( $mylink ):
    			if ( $mylink->megusta == 0 ):
    				$idnomegusta = $wpdb->query( "DELETE FROM seguidor_images WHERE seguidor_id=$seguidor_id AND images_id=$post_id" );
    				if ( $idnomegusta ): echo json_encode( array( "status"=>"ok" ) );
    				else: echo json_encode( array( "status"=>"error al eliminar" ) );
    				endif;
    			else:
	    			$idnomegusta = $wpdb->update( 
												'seguidor_images', 
												array( 
													'want' => 0	// integer (number) 
												), 
												array( 'seguidor_id' => $seguidor_id , 'images_id' => $post_id), 
												array( 
													'%d'	// value1
												), 
												array( '%d', '%d') 
											);
	    			if ( $idnomegusta ): echo json_encode( array( "status"=>"ok" ) );
					else: echo json_encode( array( "status"=> "error" ) );
					endif;
				endif;
			else: echo json_encode( array( "status"=> "error, no existe" ) );
			endif;
    	else: echo "That E-mail doesn't belong to any registered users on this site";
	  	endif;
		break;

    case "seguirfriend":
    	$u_email = $_GET['u_email'];
    	$s_id = $_GET['s_id'];
    	if (empty($s_id)){ $s_id = email_exists( $_GET['emailfriend'] ); }
    	else { $s_id = $wpdb->get_var( "SELECT ID FROM wp_users where facebook_id = '$s_id'" ); }
    	if ( !empty($s_id) && !empty($u_email) ):
	        if ( email_exists( $u_email ) ): 
	        	$u_id = email_exists( $u_email );
				//$idinsert = $wpdb->insert( 'usuario_seguidor', array( 'usuario_id' => $u_id, 'seguidor_id' => $s_id ), array( '%d', '%d' ) );
				if ($s_id):
					$idinsert = $wpdb->query("INSERT INTO usuario_seguidor (usuario_id,seguidor_id) VALUES ('$u_id','$s_id')");
					if ( $idinsert ): 
						echo json_encode( array( "status"=>"ok" ) );
						// notificacion tipo 21 (Juan te esta siguiendo)
		$user_info = get_userdata( $u_id );
		$author = $user_info->display_name;
		$ruta_thumbnail = get_user_meta( $u_id, 'ruta_thumbnail', true );
		$wpdb->query("INSERT INTO notificaciones (user_id,type,target_id, caller_name, caller_thumbnail, target_email) VALUES ('$s_id','21', '$u_id','$author', '$ruta_thumbnail', '$u_email')");
						
					else: echo json_encode( array( "status"=> "error", "desc"=>"no se puede seguir al usuario." ) );
					endif;
				else: echo json_encode( array( "status"=> "error", "desc"=>"usuario a seguir inexistente en la app." ) );
				endif;
				//$idinsert = $wpdb->insert_id;
		    else: echo json_encode( array( "status"=>"inexistente", "desc"=>"usuario inexistente en la app." ) );
	  		endif;
	  	else: echo json_encode( array( "status"=>"error", "desc"=>"falta información." ) );
	  	endif;
	  	
    	break;

    case "noseguirfriend": $u_email = $_GET['u_email']; $s_id = $_GET['s_id'];
	        if ( email_exists( $u_email ) ): $u_id = email_exists( $u_email );
	        	if (empty($s_id)){ $s_id = email_exists( $_GET['emailfriend'] ); }
    			else { $s_id = $wpdb->get_var( "SELECT ID FROM wp_users where facebook_id = '$s_id'" ); }
				//$idinsert = $wpdb->insert( 'usuario_seguidor', array( 'usuario_id' => $u_id, 'seguidor_id' => $s_id ), array( '%d', '%d' ) );
				$iddelete = $wpdb->query("DELETE FROM usuario_seguidor WHERE usuario_id='$u_id' AND seguidor_id='$s_id'");
				//$idinsert = $wpdb->insert_id;
					if ( $iddelete ): echo json_encode( array( "status"=>"ok" ) );
					else: echo json_encode( array( "status"=> "error" ) );
					endif;
		    else: echo json_encode( array( "status"=>"inexistente" ) );
	  		endif;
    	break;

	case "getpostlistimage": $email=$_GET['email']; if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
		global $post;
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
	    if ( email_exists( $email ) ): $id=email_exists($email); $ruta_thumbnail = get_user_meta( $id, 'ruta_thumbnail', true ); 
	    	$author_query = array('posts_per_page' => $posts_per_page, 'author' => $id, 'offset' => $offset );
			$the_query = new WP_Query( $author_query );
			while ( $the_query->have_posts() ) :
				$the_query->the_post();
				$id = get_the_author_meta( 'ID' );
				$user_info = get_userdata( $id );
				$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumbnailImagen );
				$imge[] = array( "id"=>$post->ID,"author"=>get_the_author(), "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend"=>$user_info->user_email );
			endwhile;
				echo json_encode($imge);
		else: echo "That E-mail doesn't belong to any registered users on this site";
	    endif;
    break;

    case "otrosprotienda": $post_id=$_GET['post_id']; $tienda=$_GET['tienda']; $tipo=$_GET['tipo'];
		global $post; require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
		$tienda_id = $wpdb->get_results("SELECT t.post_id FROM wp_users u, tienda_post t WHERE u.display_name ='$tienda' AND u.ID = t.tienda_id AND t.post_id != '$post_id' ORDER BY t.post_id DESC" );
		foreach ($tienda_id as $key): $tienda_post[] = $key->post_id; endforeach;
		if ($tienda_post):
			$author_query = array('post__in' => $tienda_post, 'posts_per_page' => 4 );
			$the_query = new WP_Query( $author_query );
			while ( $the_query->have_posts() ):
				$the_query->the_post();
				$id = get_the_author_meta( 'ID' );
				$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id(), 'thumbnail' ); 
				$imge[] = array( "post_id"=>$post->ID,"author"=>get_the_author(), "images"=> $meta_values[0] );
			endwhile;
			//print_r($imge);
			echo json_encode($imge);
		endif;
    	break;

    case "marcanotificacionleida":
//TOKEN TODO	if ( empty($_GET['token']) || !isset($_GET['token']) ) { break; }
      if ( empty($_GET['id']) || !isset($_GET['id']) ) { break; }
      $not = $_GET['id'];
      $wpdb->update( 'notificaciones', array('is_read' => 1),array( 'id' => $not ), array('%d'), array( '%d'));
      echo json_encode( array( "status"=> 'ok' ) );
      break;

    case "contarnotificaciones": 
      require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
      $notification_number = $wpdb->get_var("SELECT count(*) from notificaciones where user_id = '$current_user_id' AND is_read=0" );
      echo json_encode( array( "count" => $notification_number ) );
      break;

    case "testfriends": 
      require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
      $access_token = $wpdb->get_var("select meta_value from wp_usermeta where meta_key='fb_token' limit 1" );
      $fql_query_url = 'https://graph.facebook.com/'
                        . 'fql?q=SELECT+uid2+FROM+friend+WHERE+uid1=me()'
                        . '&access_token=' . $access_token;
      $fql_query_result = file_get_contents($fql_query_url);
      $fql_query_obj = json_decode($fql_query_result, true);
      echo '<pre>';
      print_r("query results:");
$result = array_flatten($fql_query_obj["data"]);
$fbids = array_flatten($wpdb->get_results("select u.facebook_id from wp_users as u where  u.facebook_id != '0'", "ARRAY_N" ));

$intersection = array_intersect($result, $fbids);

      print_r($intersection);
      echo '</pre>';

      break;

    case "getnotificaciones": 
      if ( empty($_GET['email']) || !isset($_GET['email']) ) { echo "[]"; break; }
      if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
      $email 	= $_GET['email'];
      $uid 	= email_exists($email);
      require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
      $ms 	= $wpdb->get_results("SELECT * from notificaciones where user_id = '$uid' ORDER BY ID DESC LIMIT 20 offset $offset" ); 
      $msgs = array();
      foreach($ms as $m):
        $msgs[] = array("id"=>$m->id, "read"=>$m->is_read, "type"=>$m->type, "target_id"=>$m->target_id, "caller_name"=>$m->caller_name, "caller_thumbnail"=> $m->caller_thumbnail, "caller_email"=>$m->target_email);
      endforeach;
      echo json_encode($msgs);
      break;

    case "catalogocomunidad": $display_name=$_GET['display_name']; if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
		global $post; require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
		$tienda_id = $wpdb->get_results("SELECT t.post_id FROM wp_users u, tienda_post t WHERE u.display_name='$display_name' AND u.ID = t.tienda_id ORDER BY t.post_id DESC" );
		foreach ($tienda_id as $key): $tienda_post[] = $key->post_id; endforeach;
		if ($tienda_post):
			$author_query = array('post__in' => $tienda_post, 'offset' => $offset, 'posts_per_page' => $posts_per_page );
			$the_query = new WP_Query( $author_query );
			while ( $the_query->have_posts() ):
				$the_query->the_post();
				$id = get_the_author_meta( 'ID' );
				$user_info = get_userdata( $id );
				$ruta_thumbnail = get_user_meta( $id, 'ruta_thumbnail', true );
				$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumbnailImagen ); 
				$imge[] = array( "id"=>$post->ID,"author"=>get_the_author(), "images"=> $meta_values[0], "ruta_thumbnail"=> $ruta_thumbnail, "emailfriend"=>$user_info->user_email );
			endwhile;
			echo json_encode($imge);
		endif;
    	break;

    case "buscarpersonas": 
    	$search = $_GET['search'];
    	if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
    	$user_query = $wpdb->get_results("SELECT w.ID, w.user_email, w.display_name, w.facebook_id FROM wp_users w, wp_usermeta u WHERE w.ID = u.user_id AND u.meta_key =  'wp_capabilities' AND u.meta_value LIKE  '%subscriber%' AND w.display_name LIKE '%$search%' LIMIT $offset,$posts_per_page");
		foreach ($user_query as $user):
			$ruta_thumbnail = get_user_meta( $user->ID, 'ruta_thumbnail', true );
			$query[] = array('ruta_thumbnail' => $ruta_thumbnail, 'user_email' => $user->user_email, 'facebook_id' => $user->facebook_id, 'display_name' => $user->display_name);
		endforeach;
		echo json_encode( $query );
    break;

    case "buscartiendas": 
    	$search = $_GET['search'];
    	if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
    	$user_query = $wpdb->get_results("SELECT w.ID, w.user_email, w.display_name FROM wp_users w, wp_usermeta u WHERE w.ID = u.user_id AND u.meta_key =  'wp_capabilities' AND u.meta_value LIKE  '%tienda%' AND w.display_name LIKE '%$search%' LIMIT $offset,$posts_per_page");
		echo json_encode( $user_query );
    break;

    case "buscarmarcas": 
    	$search = $_GET['search'];
    	if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
    	$user_query = $wpdb->get_results("SELECT t.term_id, t.name FROM wp_term_taxonomy AS tt, wp_terms AS t WHERE tt.term_id = t.term_id AND tt.taxonomy =  'marca' AND t.name LIKE '%$search%'");
		echo json_encode( $user_query );
    break;

    case "buscarprendas": 
    	$search = $_GET['search'];
    	if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
    	$user_query = $wpdb->get_results("SELECT t.term_id, t.name FROM wp_term_taxonomy AS tt, wp_terms AS t WHERE tt.term_id = t.term_id AND tt.taxonomy =  'prenda' AND t.name LIKE '%$search%'");
		echo json_encode( $user_query );
    break;

    case "maspopulares": if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
		global $post; require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
	    	$popular = $wpdb->get_results( "SELECT images_id , count(`want`) as likes FROM seguidor_images group by images_id order by likes DESC" );
	    	foreach( $popular as $pop ): $popular_post[] = $pop->images_id; endforeach;		    	
	    		$author_query = array('post__in' => $popular_post, 'offset' => $offset, 'posts_per_page' => $posts_per_page, 'orderby' => 'post__in' );
				$the_query = new WP_Query( $author_query );
				while ( $the_query->have_posts() ):
					$the_query->the_post();
					$postid = get_the_ID();
					if ( get_post_status ( $postid ) == 'publish' ):
			    		$id = get_the_author_meta( 'ID' );
			    		$user_info = get_userdata( $id );
			    		$ruta_thumbnail = get_user_meta( $id, 'ruta_thumbnail', true );
			    		$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumbnailImagen ); 
			    		$imge[] = array( "id"=>$postid,"author"=>get_the_author(), "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend"=>$user_info->user_email );
		    		endif;
		    	endwhile;
        echo json_encode( $imge );
        //else: echo "That E-mail doesn't belong to any registered users on this site";
	  	//endif;
    break;

    case "prendascercanas": if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
		global $post; require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
	    	$query_string = "SELECT wp_posts.ID, wp_posts.post_author, tienda.ID as user_id, author.user_nicename, author.user_email,
				(6371 * acos(cos(radians(" . $_GET['lat'] . ")) * cos(radians(usermeta_latitud.meta_value)) * cos(radians(usermeta_longitud.meta_value) - radians(" . $_GET['lng'] . ")) + sin(radians(" . $_GET['lat'] . ")) * sin(radians(usermeta_latitud.meta_value)))) AS distancia
				FROM wp_posts
				LEFT OUTER JOIN wp_users author ON author.ID = wp_posts.post_author
				INNER JOIN tienda_post ON tienda_post.post_id = wp_posts.ID
				LEFT OUTER JOIN wp_users tienda ON tienda.ID = tienda_post.tienda_id
				LEFT OUTER JOIN wp_usermeta usermeta_latitud ON usermeta_latitud.user_id = tienda.ID
				LEFT OUTER JOIN wp_usermeta usermeta_longitud ON usermeta_longitud.user_id = tienda.ID
				LEFT OUTER JOIN wp_postmeta postmeta_thumbnail ON postmeta_thumbnail.post_id = wp_posts.ID
				WHERE usermeta_latitud.meta_key LIKE 'latitud'
				AND usermeta_longitud.meta_key LIKE 'longitud'
				AND tienda_post.tienda_id = tienda.ID
				AND wp_posts.post_status LIKE 'publish'
				AND postmeta_thumbnail.meta_key LIKE '_thumbnail_id'
				ORDER BY distancia, wp_posts.ID
		    	LIMIT 20
		    	OFFSET " . $offset;

	    	$prendascercanas = $wpdb->get_results( $query_string );
	    	foreach( $prendascercanas as $prenda ):
	    			$ruta_thumbnail = get_user_meta( $prenda->post_author, 'ruta_thumbnail', true );
		    		$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id($prenda->ID), $thumbnailImagen );
	    			$imge[] = array( "id"=>$prenda->ID,"author"=>$prenda->user_nicename, "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend"=>$prenda->user_email, "distancia_km"=>$prenda->distancia );
		    endforeach;
        echo json_encode( $imge );
        //else: echo "That E-mail doesn't belong to any registered users on this site";
	  	//endif;
    break;

    case 'ranking':
    	$acu=0;
    	$email=$_GET['email'];
		global $post;
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
	    if ( email_exists( $email ) ): $id=email_exists($email);
	    	$author_query = array( 'author' => $id );
			$the_query = new WP_Query( $author_query );
			while ( $the_query->have_posts() ) :
				$the_query->the_post(); $post_id = $post->ID;
				$want = $wpdb->get_var( "SELECT count(`want`) as likes FROM seguidor_images WHERE images_id = '$post_id' AND want !=0 group by images_id order by likes desc" );
				$acu += $want;
			endwhile;
				echo json_encode( array('puntos' => $acu*3, 'acumulador' => $acu ) );
		else: echo "That E-mail doesn't belong to any registered users on this site";
	    endif;
    	break;

    case "getpostlistimagetodos": if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
		global $post;
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
	    $author_query = array('posts_per_page' => $posts_per_page, 'offset' => $offset );
		$the_query = new WP_Query( $author_query );
		while ( $the_query->have_posts() ) :
			$the_query->the_post();
			$id = get_the_author_meta( 'ID' );
			$user_info = get_userdata( $id );
			$ruta_thumbnail = get_user_meta( $id, 'ruta_thumbnail', true ); 
			$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumbnailImagen ); 
			$imge[] = array( "id"=>$post->ID,"author"=>get_the_author(), "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend"=>$user_info->user_email );
		endwhile;
			echo json_encode($imge);
    	break;

    case "getpostimage": $post_id=$_GET['id'];
		global $post;
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
	    $post_info = get_post($post_id);
	    $user = get_userdata( $post_info->post_author );
		//$user = get_user_by('id', $post_info->post_author);
		$ruta_thumbnail = get_user_meta( $user->ID, 'ruta_thumbnail', true );
		$prendacat = wp_get_post_terms($post_id, 'prenda');
		foreach($prendacat as $cat) if ($cat->name != 'null'){$prenda = $cat->name;}else{$prenda='';}
        
        //$marcacat = wp_get_post_terms($post_id, 'marca');
        //foreach($marcacat as $cat) if ($cat->name != 'null'){$marca = $cat->name;}else{$marca='';}

        $tienda_id = $wpdb->get_var( "SELECT tienda_id FROM tienda_post WHERE post_id='$post_id'" );

        if ($user->display_name) { $display_name = $user->display_name; }
        else{ $display_name = '';}
		$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $largeImagen );

		if ( user_can($user, 'administrator') || user_can($user, 'subscriber') ) { $role = 'usuario'; }
		elseif ( user_can($user->ID, 'tienda') ) { $role = 'tienda'; }

		//Editado por fabian@acid.cl.  
        if ($tienda_id): 
        	//uso variables independientes para información de tienda, por que si no existe tienda_id, tienda_info no se crea y arroja warning
        	$tienda_info = get_userdata( $tienda_id );
        	$tienda_display_name = $tienda_info->display_name;
        	$ciudad = get_user_meta($tienda_id, 'ciudad', true);
        	$direccion = get_user_meta($tienda_id, 'direccion', true);
       	//Si no hay tienda_id, pero el usuario es una tienda, se le agrega información del usuario a los valores de tienda
        elseif ($role == 'tienda'):
        	$tienda_display_name = $user->display_name; 
     		$tienda_id = $user->ID; 
     		$ciudad = get_user_meta($user->ID, 'ciudad', true);
        	$direccion = get_user_meta($user->ID, 'direccion', true);
        //Si no, es que la foto la subió un usuario pero no agregó tienda, por lo que los valores correspondientes van nulos
        else:	
        	$tienda_display_name = null; 
        	$ciudad = null;
        	$direccion = null;
        endif;

		echo json_encode( array( "role"=>$role ,"direccion" => $direccion, "ID_tienda" => $tienda_id, "ciudad" => $ciudad, "emailfriend"=>$user->user_email , "post_author"=>$post_info->post_author,"nombre"=>$display_name,"prendacat"=>$prenda,"tiendacat"=>$tienda_display_name,"comment_count"=>$post_info->comment_count, "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail ) );
		//echo json_encode( array( "direccion" => $direccion, "ID_tienda" => $tienda_info->ID, "ciudad" => $ciudad, "emailfriend"=>$user->user_email , "post_author"=>$post_info->post_author,"nombre"=>$user->first_name,"prendacat"=>$prenda,"marcacat"=>$marca,"tiendacat"=>$tienda_info->display_name,"comment_count"=>$post_info->comment_count, "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail ) );
		break;

	case "ingresartienda":
	    	$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
	    	$nombre = $_GET["nom"]; $email= $nombre."@".$nombre.".cl";
			$user_id = wp_insert_user( array ('user_login' => $nombre, 'user_pass' => $random_password, 'user_nicename' => $nombre, 'user_email' => $email, 'role' => 'tienda') );
			if ($user_id):
				add_user_meta( $user_id, 'latitud', $_GET["lat"]);
				add_user_meta( $user_id, 'longitud', $_GET["lon"]);
				add_user_meta( $user_id, 'direccion', $_GET["dir"]);
				add_user_meta( $user_id, 'ciudad', $_GET["ciu"]);
				add_user_meta( $user_id, 'provincia', $_GET["pro"]);
				add_user_meta( $user_id, 'region', $_GET["reg"]);
				add_user_meta( $user_id, 'pais', $_GET["pais"]);
				// actualiza campos de la tabla wp_usermeta
				update_user_meta( $user_id, 'first_name', $nombre);
				update_user_meta( $user_id, 'display_name', $nombre);
				update_user_meta( $user_id, 'user_nicename', $nombre);
				update_user_meta( $user_id, 'nickname', $nombre);
				wp_new_user_notification( $user_id, $random_password );
				echo json_encode( array( "status"=>"ok" ) );
			else:
				echo json_encode( array( "status"=>"error", "desc"=>"ocurrio un error al agregar la tienda.") );
			endif;
    	break;

    case "ingresarmarca":
    	$term = $_GET['marca'];
    	if( !empty($term) ):
    		$marca = wp_insert_term( $term, 'marca' );
    		echo json_encode( $marca );
    	else: echo json_encode( array( "status"=>"error" ) );
    	endif;
    	break;

    case "eliminarpost":
    	$post_id = $_GET['post_id'];
    	if( !empty( $post_id ) && email_exists($_GET['email']) ):
    		$user_id = email_exists($_GET['email']);
    		if ( get_post_status ( $post_id ) == 'publish' ):
    			//$attachmentid = wp_delete_attachment( get_post_thumbnail_id( $post_id ) );
	    		//if ($attachmentid):
	    			$del_post = wp_delete_post($post_id);
	    			if ($del_post):
	    				$del_seguidor_imagen = $wpdb->query( "DELETE FROM seguidor_images WHERE images_id = $post_id AND seguidor_id = $user_id" );
		    			if ( $del_seguidor_imagen ): echo json_encode( array( "status"=>"ok" ) );
		    			else: echo json_encode( array( "status"=>"ok", "desc"=>"error 300." ) );
		    			endif;
	    			else: echo json_encode( array( "status"=>"error", "desc"=>"error 301." ) );
		    		endif;
	    		//else: echo json_encode( array( "status"=>"error", "desc"=>"error 302." ) );
		    	//endif;
    		else: echo json_encode( array( "status"=>"error", "desc"=>"No existe la imagen" ) );
    		endif;
    	else: echo json_encode( array( "status"=>"error", "desc"=>"sin contenido" ) );
    	endif;
    	break;

    case "gettienda":
	    if ( empty($_GET['idtienda']) ):
	    	$query_string = "SELECT wp_users . *,
	    		(6371 * acos(cos(radians(" . $_GET['lat'] . ")) * cos(radians(usermeta_latitud.meta_value)) * cos(radians(usermeta_longitud.meta_value) - radians(" . $_GET['lng'] . ")) + sin(radians(" . $_GET['lat'] . ")) * sin(radians(usermeta_latitud.meta_value)))) AS distancia
				FROM wp_users
				LEFT OUTER JOIN wp_usermeta usermeta_role ON wp_users.ID = usermeta_role.user_id
				LEFT OUTER JOIN wp_usermeta usermeta_latitud ON wp_users.ID = usermeta_latitud.user_id
				LEFT OUTER JOIN wp_usermeta usermeta_longitud ON wp_users.ID = usermeta_longitud.user_id
				WHERE usermeta_role.meta_key LIKE 'wp_capabilities'
				AND usermeta_role.meta_value LIKE '%tienda%'
				AND usermeta_latitud.meta_key LIKE 'latitud'
				AND usermeta_longitud.meta_key LIKE 'longitud'
				ORDER BY distancia
				LIMIT 0 , 30";
	    	$user_query = $wpdb->get_results($query_string);
	    	if ( !empty( $user_query ) ):
				foreach ( $user_query as $user ):
					$latitud = get_user_meta( $user->ID, 'latitud', true );
					$longitud = get_user_meta( $user->ID, 'longitud', true );
					$direccion = get_user_meta( $user->ID, 'direccion', true );
					$info[] = array( "ID"=>$user->ID, "display_name"=>$user->display_name, "latitud"=>$latitud, "longitud"=>$longitud, "direccion"=>$direccion, "distancia_km"=>$user->distancia );
				endforeach;
				echo json_encode( $info );
			else: echo json_encode( array( "status"=>"error", "desc"=>"no se encuentran tiendas." ) );
			endif;
		else:
			$user_query = new WP_User_Query( array( 'include' => array( $_GET['idtienda'] ), 'role' => 'tienda', 'meta_key' => 'ciudad', 'meta_value' => $_GET['ciu'], 'order' => 'ASC' ) );
	    	if ( !empty( $user_query->results ) ):
				foreach ( $user_query->results as $user ):
					$latitud = get_user_meta( $user->ID, 'latitud', true );
					$longitud = get_user_meta( $user->ID, 'longitud', true );
					$direccion = get_user_meta( $user->ID, 'direccion', true );
					$ruta_thumbnail = get_user_meta( $user->ID, 'ruta_thumbnail', true);
					$info = array( "ID"=>$user->ID, "email_tienda"=>$user->user_email, "display_name"=>$user->display_name, "ruta_thumbnail"=>$ruta_thumbnail, "latitud"=>$latitud, "longitud"=>$longitud, "direccion"=>$direccion );
				endforeach;
				echo json_encode( $info );
			else: echo json_encode( array( "status"=>"error", "desc"=>"no se encuentran tiendas." ) );
			endif;
			//$users = new WP_User( $_GET['idtienda'] );
			//echo json_encode( $users );
	    endif;
    	break;

    case "listcat": $taxonomy=$_GET['taxonomy'];
	  	if( taxonomy_exists($taxonomy) )
			echo json_encode( $terminos = $wpdb->get_results("SELECT t.term_id,t.name,t.slug FROM wp_term_taxonomy AS tt, wp_terms AS t WHERE tt.term_id = t.term_id AND tt.taxonomy = '$taxonomy' ORDER BY t.name ASC ") );
		break;

	case "getresultvitrinea": $terms=$_GET['terms']; $taxonomy=$_GET['tax']; $tipo=$_GET['tipo'];
		global $post;
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";

		if ($taxonomy == 'prenda') {
			if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }

			if($tipo == 'popular'){ /* Se ejecuta cuando el tipo es popular, visualiazando imagenes solo populares */
				global $post; require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
		    	$popular = $wpdb->get_results( "SELECT images_id , count(`want`) as likes FROM seguidor_images group by images_id order by likes DESC" );
		    	foreach( $popular as $pop ): $popular_post[] = $pop->images_id; endforeach;
				$args = array( 'post_type' => 'post', 'prenda' => ''.$terms.'', 'posts_per_page' => $posts_per_page, 'offset' => $offset, 'post__in' => $popular_post, 'orderby' => 'post__in' );
				$query = new WP_Query( $args );
				while ( $query->have_posts() ) :
					$query->the_post();
					$id = get_the_author_meta( 'ID' );
					$user_info = get_userdata( $id );
					$ruta_thumbnail = get_user_meta( $id, 'ruta_thumbnail', true );  
					$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumbnailImagen ); 
					$imge[] = array( "id"=>$post->ID,"author"=>get_the_author(), "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend"=>$user_info->user_email );
				endwhile;
				echo json_encode( $imge );
			}
			elseif($tipo == 'amigos'){ /* Se ejecuta cuando el tipo es amigos, visualiazando imagenes solo de amigos, es necesario el correo del usuario principal */
				$email = $_GET['email'];
				$id = email_exists($email);
		    	$amigo = '';
		    	$sigo = $wpdb->get_results( "SELECT u.ID as ID FROM usuario_seguidor as us, wp_users as u where u.ID = us.seguidor_id and us.usuario_id = '$id'" );
		    	foreach( $sigo as $si ): $amigo .= ''.$si->ID.','; endforeach;
		    	$args = array( 'post_type' => 'post', 'prenda' => ''.$terms.'', 'posts_per_page' => $posts_per_page, 'offset' => $offset, 'author' => $amigo );
				$query = new WP_Query( $args );
				while ( $query->have_posts() ) :
					$query->the_post();
					$id = get_the_author_meta( 'ID' );
					$user_info = get_userdata( $id );
					$ruta_thumbnail = get_user_meta( $id, 'ruta_thumbnail', true );  
					$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumbnailImagen ); 
					$imge[] = array( "id"=>$post->ID,"author"=>get_the_author(), "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend"=>$user_info->user_email );
				endwhile;
				echo json_encode( $imge );
			}
			//Editado por fabian@acid.cl
			elseif($tipo == 'cercanas'){ /* Se ejecuta mismo código de prendas cercanas pero filtrao por prenda */
				$query_string = "SELECT wp_posts.ID, wp_posts.post_author, tienda.ID as user_id, author.user_nicename, author.user_email,
					(6371 * acos(cos(radians(" . $_GET['lat'] . ")) * cos(radians(usermeta_latitud.meta_value)) * cos(radians(usermeta_longitud.meta_value) - radians(" . $_GET['lng'] . ")) + sin(radians(" . $_GET['lat'] . ")) * sin(radians(usermeta_latitud.meta_value)))) AS distancia
					FROM wp_posts
					LEFT OUTER JOIN wp_users author ON author.ID = wp_posts.post_author
					INNER JOIN tienda_post ON tienda_post.post_id = wp_posts.ID
					LEFT OUTER JOIN wp_users tienda ON tienda.ID = tienda_post.tienda_id
					LEFT OUTER JOIN wp_usermeta usermeta_latitud ON usermeta_latitud.user_id = tienda.ID
					LEFT OUTER JOIN wp_usermeta usermeta_longitud ON usermeta_longitud.user_id = tienda.ID
					WHERE usermeta_latitud.meta_key LIKE 'latitud'
					AND usermeta_longitud.meta_key LIKE 'longitud'
					AND tienda_post.tienda_id = tienda.ID
					AND wp_posts.post_status LIKE 'publish'
					AND tienda_post.categoria LIKE '".$terms."'
					ORDER BY distancia, wp_posts.ID
			    	LIMIT 20
			    	OFFSET " . $offset;

		    	$prendascercanas = $wpdb->get_results( $query_string );
		    	foreach( $prendascercanas as $prenda ):
	    			$ruta_thumbnail = get_user_meta( $prenda->post_author, 'ruta_thumbnail', true );
		    		$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id($prenda->ID), $thumbnailImagen );
	    			$imge[] = array( "id"=>$prenda->ID,"author"=>$prenda->user_nicename, "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend"=>$prenda->user_email, "distancia_km"=>$prenda->distancia );
			    endforeach;
		        echo json_encode( $imge );
			}
			
			if( empty($tipo) ){ /* Si el tipo es vacio, se realiza una busqueda generalizada de prendas. */
				$args = array( 'post_type' => 'post', 'prenda' => ''.$terms.'', 'posts_per_page' => $posts_per_page, 'offset' => $offset );
				$query = new WP_Query( $args );
				while ( $query->have_posts() ) :
					$query->the_post();
					$id = get_the_author_meta( 'ID' );
					$user_info = get_userdata( $id );
					$ruta_thumbnail = get_user_meta( $id, 'ruta_thumbnail', true );  
					$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumbnailImagen ); 
					$imge[] = array( "id"=>$post->ID,"author"=>get_the_author(), "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend"=>$user_info->user_email );
				endwhile;
				echo json_encode( $imge );
			}
		}
		if ($taxonomy == 'marca') {
			if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
			$args = array(
				'post_type' => 'post',
				'marca' => ''.$terms.'',
				'posts_per_page' => $posts_per_page,
				'offset' => $offset
			);
			$query = new WP_Query( $args );
			while ( $query->have_posts() ) :
				$query->the_post();
				$id = get_the_author_meta( 'ID' );
				$user_info = get_userdata( $id );
				$ruta_thumbnail = get_user_meta( $id, 'ruta_thumbnail', true );  
				$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumbnailImagen ); 
				$imge[] = array( "id"=>$post->ID,"author"=>get_the_author(), "images"=> $meta_values[0], "ruta_thumbnail"=>$ruta_thumbnail, "emailfriend"=>$user_info->user_email );
			endwhile;
			echo json_encode( $imge );
		}
		break;

	case "getcomentario":
			$post_id = $_GET['post_id'];
			if ( !empty($post_id) ):
				$args = array( 'status' => 'approve', 'post_id' => $post_id );
				$comments = get_comments( $args );
				foreach($comments as $comment) :
					$ruta_thumbnail = get_user_meta( $comment->user_id, 'ruta_thumbnail', true );
					$co[] = array( 'comment_author_email' => $comment->comment_author_email, 'comment_user_ID' => $comment->user_id, 'comment_ID' => $comment->comment_ID, 'ruta_thumbnail' => $ruta_thumbnail, 'comment_author' => $comment->comment_author, 'comment_content' => $comment->comment_content );
				endforeach;
				echo json_encode( $co );
			else: echo json_encode( array( "status"=> "Falta información" ) );
			endif;
		break;

	case "delcomentario":
			$comment_id = $_GET['comment_id'];
			if ( wp_delete_comment( $comment_id ) ):
				echo json_encode( array( 'status' => 'OK', 'comment_id' => $comment_id ) );
			else: echo json_encode( array( "status"=> "ERROR", 'comment_id' => $comment_id ) );
			endif;
		break;
		
	case "newcomentario": 
		$author_email = $_GET['email'];
		if ( email_exists( $author_email ) ):
			$user_id = email_exists( $author_email );
			$author = $_GET['user'];
			$post_id = $_GET['post_id'];
			$content = $_GET['content'];
			if ( !empty($author) && !empty($post_id) && !empty($content) && !empty($user_id) && !empty($author_email) ):
				$time = current_time('mysql');
				$data = array(
				    'comment_post_ID' => $post_id,
				    'comment_author' => $author,
				    'comment_author_email' => $author_email,
				    'comment_content' => $content,
				    'comment_parent' => 0,
				    'user_id' => $user_id,
				    'comment_date' => $time,
				    'comment_approved' => 1,
				);
				$ruta_thumbnail = get_user_meta( $user_id, 'ruta_thumbnail', true );
				$id_comment = wp_insert_comment($data);
				//array_push($id_comment, "ruta_thumbnail" => $ruta_thumbnail );
				//$info_comment = get_comment( $id_comment );
				//$info_comment = (array)$info_comment[0];
				//$ruta_thumbnail = get_user_meta( $info_comment['user_id'], 'ruta_thumbnail', true );
				//$comments['ruta_thumbnail'] = $ruta_thumbnail;

				if($id_comment):
					// notificacion tipo 12 (comente una prenda) 
 					echo json_encode( $info_comment = get_comment( $id_comment ) );
					global $post;
                                        require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
                                        $post_info = get_post($post_id);
                                        $owner = get_userdata( $post_info->post_author );
                                        if($owner->ID != $user_id):
					  $wpdb->query("INSERT INTO notificaciones (user_id,type,target_id, caller_name, caller_thumbnail) VALUES ('$owner->ID','12', '$post_id','$author', '$ruta_thumbnail')");
					endif;
					
				else: echo json_encode( array( "status"=> "error" ) );
				endif;

			else: echo json_encode( array( "status"=> "Falta información" ) );
			endif;
			
		else: echo json_encode( array( "status"=> "usuario inexistente" ) );
	    endif;
		break;

	case "buscarprendamapa": $categoria = $_GET['prenda']; $latitud = $_GET['lat']; $longitud = $_GET['lng'];
		$query_string = "SELECT wp_users.ID,
			(6371 * acos(cos(radians($latitud)) * cos(radians(usermeta_latitud.meta_value)) * cos(radians(usermeta_longitud.meta_value) - radians($longitud)) + sin(radians($latitud)) * sin(radians(usermeta_latitud.meta_value)))) AS distancia
			FROM wp_users
			LEFT OUTER JOIN wp_usermeta usermeta_latitud ON wp_users.ID = usermeta_latitud.user_id
			LEFT OUTER JOIN wp_usermeta usermeta_longitud ON wp_users.ID = usermeta_longitud.user_id
			WHERE usermeta_latitud.meta_key LIKE 'latitud'
			AND usermeta_longitud.meta_key LIKE 'longitud'
			AND wp_users.ID IN ( SELECT tienda_id FROM tienda_post WHERE categoria = '$categoria' GROUP BY tienda_id )
			ORDER BY distancia
			LIMIT 0 , 30";
		$res_id = $wpdb->get_results($query_string);
		foreach( $res_id as $res ):
			//$user_info = get_userdata( $res->user_id );
			$latitud = get_user_meta( $res->ID, 'latitud', true );
			$longitud = get_user_meta( $res->ID, 'longitud', true );
			$nombre = get_user_meta( $res->ID, 'display_name', true );
			$ciudad = get_user_meta( $res->ID, 'ciudad', true );
			$tiendas[] = array( "latitud"=>$latitud,"longitud"=>$longitud, "nombre"=>$nombre, "tienda_id"=>$res->ID, "prenda"=>$prenda, "ciudad"=>$ciudad, "distancia_km"=>$res->distancia );
		endforeach;
		echo json_encode( $tiendas );
		break;

	case "getpostprendatienda": $categoria = $_GET['prenda']; $tienda_id = $_GET['tienda_id']; if ( empty($_GET['offset']) || !isset($_GET['offset']) ) { $offset = 0; } else{ $offset=$_GET['offset']; }
		$posts = $wpdb->get_results("SELECT post_id FROM tienda_post WHERE tienda_id = '$tienda_id' AND categoria = '$categoria' LIMIT $offset,$posts_per_page");
		foreach ($posts as $key): $tienda_post[] = $key->post_id; endforeach;
		if ($tienda_post):
			$author_query = array('post__in' => $tienda_post);
			$the_query = new WP_Query( $author_query );
			while ( $the_query->have_posts() ):
				$the_query->the_post();
				$id = get_the_author_meta( 'ID' );
				$user_info = get_userdata( $id );
				$ruta_thumbnail = get_user_meta( $id, 'ruta_thumbnail', true );
				$meta_values = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumbnailImagen ); 
				$imge[] = array( "post_id"=>$post->ID,"author"=>get_the_author(), "images"=> $meta_values[0], "ruta_thumbnail"=> $ruta_thumbnail, "emailfriend"=>$user_info->user_email );
			endwhile;
			echo json_encode($imge);
		endif;
		break;

	case "denunciarimagen": $post_id = $_GET['post_id']; $motivo = $_GET['motivo']; $denunciante = $_GET['denunciante'];
		if ( email_exists( $denunciante ) ):
			$user_info = get_userdata( email_exists( $denunciante ) );
			if ( !empty($post_id) && !empty($motivo) ):
				if ( get_post_status ( $post_id ) == 'publish' ):
					wp_new_post_denuncia_notification( $post_id, $motivo, $user_info->ID );
				endif;
			endif;
		endif;
		break;

	case "cambiarimagen":
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-admin/includes/file.php";
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-admin/includes/image.php";
		$email=$_GET['email'];
		if ( email_exists( $email ) ):
			$user_id = email_exists( $email );
		endif;
		$imagen = $_FILES['file'];
		$fecha = date('Y/m');

		echo "fecha lala: ".$fecha;
		echo "Upload lala: " . $imagen["name"];
		echo " Type lala: " . $imagen["type"];
		echo " Size lala: " . ($imagen["size"] / 1024) . "Kb ";
		echo " Stored in lala: " . $imagen["tmp_name"];

		$upload_overrides = array( 'test_form' => FALSE );
		// load up a variable with the upload direcotry
		$uploads = wp_upload_dir();
		$uploads['baseurl'];

		$file_array = array(
				'name' 		=> $imagen['name'],
				'type'		=> $imagen['type'],
				'tmp_name'	=> $imagen['tmp_name'],
				'error'		=> $img['error'],
				'size'		=> $imagen['size'] );
			// check to see if the file name is not empty
			if ( !empty( $file_array['name'] ) ) {

				$uploaded_file = wp_handle_upload( $file_array, $upload_overrides );

				$post_image = $uploads['baseurl'] . '/' . $fecha . '/' . basename( $uploaded_file['file'] );

				update_user_meta( $user_id, 'ruta_thumbnail', $post_image);
			}

		break;

	case "upload":
		// if you have this in a file you will need to load "wp-load.php" to get access to WP functions.  If you post to "self" with this code then WordPress is by default loaded
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-load.php";
		// require two files that are included in the wp-admin but not on the front end.  These give you access to some special functions below.
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-admin/includes/file.php";
		require $_SERVER['DOCUMENT_ROOT'] . "/wp-admin/includes/image.php";
		$email=$_GET['email']; $prenda=$_GET['prenda']; $marca=$_GET['marca']; $tienda=$_GET['tienda'];
		if ( email_exists( $email ) ):
			$user_id = email_exists( $email );
		endif;
		
		$imagen = $_FILES['file'];
		$grados = 90;

		$fecha = date('Y/m');
		/*
		echo "fecha: ".$fecha;
		echo "Upload: " . $_FILES["file"]["name"];
		echo " Type: " . $_FILES["file"]["type"];
		echo " Size: " . ($_FILES["file"]["size"] / 1024) . "Kb ";
		echo " Stored in: " . $_FILES["file"]["tmp_name"];
		*/

		//echo "fecha lala: ".$fecha;
		//echo "Upload lala: " . $imagen["name"];
		//echo " Type lala: " . $imagen["type"];
		//echo " Size lala: " . ($imagen["size"] / 1024) . "Kb ";
		//echo " Stored in lala: " . $imagen["tmp_name"];
		
		// required for wp_handle_upload() to upload the file
		$upload_overrides = array( 'test_form' => FALSE );
		// load up a variable with the upload direcotry
		$uploads = wp_upload_dir();
		$uploads['baseurl'];
		
		// create an array of the $_FILES for each file
			$file_array = array(
				'name' 		=> $imagen['name'],
				'type'		=> $imagen['type'],
				'tmp_name'	=> $imagen['tmp_name'],
				'error'		=> $img['error'],
				'size'		=> $imagen['size'] );
			// check to see if the file name is not empty
			if ( !empty( $file_array['name'] ) ):

				// Create post object
				$my_post = array(
				  'post_title'    => wp_strip_all_tags( $imagen['name'] ),
				  'post_content'  => '',
				  'post_status'   => 'publish',
				  'post_author'   => $user_id,
				  'post_category' => array(1) 
				);

			 	// upload the file to the server
			    $uploaded_file = wp_handle_upload( $file_array, $upload_overrides );

			    if ( !$uploaded_file["error"]):

				    // Insert the post into the database
					$post_parent = wp_insert_post( $my_post, true );
					//echo " #### POST: ".$post_parent;

					//"if" agregado por fabian@acid.cl
					if($tienda){
						$tienda_id = $wpdb->get_var("SELECT ID FROM wp_users WHERE display_name='$tienda' ");
						if ( $tienda_id ):
							$postienda = $wpdb->get_results( "INSERT INTO tienda_post (tienda_id, post_id, categoria) VALUES ( '$tienda_id', '$post_parent', '$prenda')" );
							//echo "tienda: ".$tienda_id;
							//echo "posttienda: ".$postienda;
						endif;
					}

					//$cat_pre = get_term_by('id', $prenda, $taxonomy_p);
					//$cat_mar = get_term_by('id', $marca, $taxonomy_m);
					//$a = wp_set_object_terms( $post_parent, $marca, 'marca' );
					$b = wp_set_object_terms( $post_parent, $prenda, 'prenda' );
					//wp_set_post_terms( $post_parent, $marca, 'marca' );
					//wp_set_post_terms( $post_parent, $prenda, 'prenda' );

					// checks the file type and stores in in a variable
				    $wp_filetype = wp_check_filetype( basename( $uploaded_file['file'] ), null );
				    // set up the array of arguments for "wp_insert_post();"
				    $attachment = array(
				    	'post_mime_type' => $wp_filetype['type'],
				    	'post_title' => preg_replace('/\.[^.]+$/', '', basename( $uploaded_file['file'] ) ),
				    	'post_content' => '',
				    	'post_author' => $user_id,
				    	'post_status' => 'inherit',
				    	'post_type' => 'attachment',
				    	'post_parent' => $post_parent,
				    	'guid' => $uploads['baseurl'] . '/' . $fecha . '/' . basename( $uploaded_file['file'] ) );
				    $meta_value = '/' .$fecha. '/' . basename( $uploaded_file['file'] );
				    $post_image = $uploads['baseurl'] . '/' . $fecha . '/' . basename( $uploaded_file['file'] );
				    // insert the attachment post type and get the ID
				    $attachment_id = wp_insert_post( $attachment );
					// generate the attachment metadata
					$attach_data = wp_generate_attachment_metadata( $attachment_id, $uploaded_file['file'] );
					// update the attachment metadata
					wp_update_attachment_metadata( $attachment_id,  $attach_data );
					add_post_meta($attachment_id, '_wp_attached_file', $meta_value);
					add_post_meta( $post_parent, '_thumbnail_id', $attachment_id);
					add_post_meta( $post_parent, 'post_image', $post_image);
					add_post_meta( $post_parent, '_post_image_attach_id', $attachment_id);
					add_post_meta( $post_parent, 'layout', 'default');
					add_post_meta( $post_parent, 'hide_post_title', 'yes');
					add_post_meta( $post_parent, 'unlink_post_title', 'yes');
					add_post_meta( $post_parent, 'hide_post_meta', 'default');
					add_post_meta( $post_parent, 'hide_post_date', 'default');
					add_post_meta( $post_parent, 'hide_post_image', 'default');
					add_post_meta( $post_parent, 'unlink_post_image', 'default');

					echo json_encode( array('status' => 'OK' ) );

				else: echo json_encode( array('status' => 'ERROR', 'desc' => $uploaded_file["error"] ) );
				endif;
			else: echo json_encode( array('status' => 'ERROR', 'desc' => 'Se extravió la imagen, intentalo nuevamente.') );
			endif;
		break;
}
/*
$wpdb->insert( 
			'encuestas', 
			array( 
				'wp_users_id' => $user_id, 
				'producto_id' => $idproducto,
				'nombre' => ''.$_POST["encuesta"].'',
				'hora_encuesta' => $hora,
				'fecha_encuesta' => $fecha
			), 
			array( 
				'%d', 
				'%s',
				'%s',
				'%s'
			) 
);
$idencuesta = $wpdb->insert_id;
$myrows = $wpdb->get_results( "SELECT idsucursal,nombre_sucursal,comuna FROM sucursal WHERE wp_users_id=".$user_id );
*/
?>
<?php
 /*
$image = wp_get_image_editor( $_FILES['file'] ); // Return an implementation that extends <tt>WP_Image_Editor</tt>
if ( ! is_wp_error( $image ) ) {
    $ima1 = $image->rotate( -90 );
    $ima2 = $image->save( '/hsphere/local/home/c376930/deemelo.rowsis.com/wp-content/uploads/2013/03/'+$_FILES['file']['name']+'.jpg' );
}
//var_dump($ima1);
//var_dump($ima2);
*/
?>
