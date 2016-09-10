<?php 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
* WooSei Integration Class
*/
class WooSei_WooCommerce_Sensei_Integration_admin {

    /**
    * Var to set the courses.
    *
    * @var array
    */
    public $courses = array();

    /**
    * Array to set the courses purchased.
    *
    * @var array
    */
    public $coursesPurchasedArray = array();
    
    /**
    * Storing courses and quantity.
    *
    * @var array
    */
    public $coursesAndQuantity = array();

    /**
    * The user id.
    *
    * @var string
    */
    public $userid;

    /**
    * To store the CNPJ (Brazilian code for companies) if the user has. 
    * 
    * @var string
    */
    public $hascnpj;    

    /**
    * Constructor to initialize. 
    */
    function __construct(){
      $this->init();
    }


    /**
    * Adding the action. 
    */
    private function init(){               
      add_action( 'woocommerce_account_content', array($this, 'woosei_show_courses_bought') );        
    }

    /**   
    * Called in the 'woocommerce_acount_content', if the user has CNPJ and bought some courses will be displayed
    */
    public function woosei_show_courses_bought(){                
      $this->userid = get_current_user_id(); 
      $curUser = wp_get_current_user();
      $this->hascnpj = get_user_meta($this->userid, 'billing_cnpj', 'true');
      $courseOrder = new Sensei_WC();
      $arrayCourseOrdersByUser = $courseOrder->get_user_product_orders( $this->userid, '' );      
      $p = 0;
      foreach ( $arrayCourseOrdersByUser as $key => $value ){      
          $courseOrderObj = new WC_Order( $value->ID );                         
          foreach ( $courseOrderObj->get_items() as $keyOrder => $courseOrderItem ){            
            $this->courses[] = $courseOrderItem['product_id']; 
            $this->coursesAndQuantity[$p]['name'] = $courseOrderItem['name'];                       
            $this->coursesAndQuantity[$p]['product_id'] = $courseOrderItem['product_id'];
            $this->coursesAndQuantity[$p]['quantity'] = $courseOrderItem['qty']; 
            $this->coursesAndQuantity[$p]['order_id'] = $courseOrderObj->id;                       
            $p++;
          }
      }      

      if( !empty( $this->hascnpj ) ){
        $this->woosei_getCoursesPurchased();        
        if( !empty( $this->coursesPurchasedArray ) ) {        
          $this->woosei_displayForm(); 
        }else{
          echo "<h2>Atenção</h2>";
          echo "<p><strong>Parece que você não tem cursos disponíveis ou eles ainda não tiveram confirmado seu pagamento.</strong></p>";
        }
      }
      
    }

    /**
    * Display the form with the purchased courses by the user who whas cnpj.
    * The form has the function to create users who will receibe some of the purchased courses.
    * Send an email with the info.
    */
    private function woosei_displayForm(){
    ?>
      <div id="wpbody" role="main">

          <h1>Seus cursos e quantidades</h1>
          <?php        
            echo "Você contratou o(s) seguinte(s) curso(s): <br>";        
            foreach( $this->coursesAndQuantity as $key ){
                echo "<strong>" . $key['quantity'] . "</strong> vaga(s) para o curso de: " .  $key['name'] . " Pedido: " . $key['order_id'] . "<br>";
            }   
            echo "<br>";        
          ?>          
          <h1>Seus colaboradores</h1>        
        
          <?php
          $user_query = new WP_User_Query( 
            array( 
              'meta_key' => 'userParent', 
              'meta_value' => $this->userid 
            ) 
          );

          foreach($user_query->get_results() as $userData){
              $idCustomer = $userData->ID;
              echo "<strong>Login:</strong> " . $userData->display_name . "<br>";
              echo "<strong>Email:</strong> " . $userData->user_email . "<br>";
              echo "<strong>CPF:</strong> " . get_user_meta($idCustomer, 'cpf', 'true');
              echo "<br><br>";
          }
          ?>              

          <h1>Inclusão de Usuários</h1>
          <i>Insira abaixo os colaboradores que deverão receber as senhas para o(s) curso(s):</i>
          <br><br>
          <form name="include" method="post" action="">
          <div id="elements" style="margin-top:20px;">
            <input type="text" name="nome[]" id="nome" placeholder="Nome Completo" required="true"/>
            <input type="text" name="email[]" id="email" placeholder="Email" required="true"/>
            <input type="text" name="cpf[]" id="cpf" placeholder="CPF" required="true"/>            
            <select name="curso[]">            
            <?php
              foreach($this->coursesPurchasedArray as $k => $v){
                echo "<option value='" . $v['ID'] . "'>" . $v['name'] . "</option>";
              }
            ?>
            </select>
          </div>
          <div id="receive">
          </div>
            <br><br>      
            <p class="submit">
            <input type="submit" name="duplicar-colaborador-curso-custom" id="duplicar-colaborador-curso-custom" value="Adicionar Novo Colaborador" class="button button-secondary" style="width: auto">              
            <input type="submit" name="enviar" value="Cadastrar Colaborador (es)" class="button button-primary" style="width:auto">
            </p>
          </form>
        

          <?php
          if( isset( $_POST['enviar'] ) ){ 

            $countUsers = count( $_POST['nome'] );
            for($w=0;$w<$countUsers;$w++){                           
              $senha = rand(0,999999);
              $safeEmail = sanitize_email( $_POST['email'][$w] );
              $safeName = sanitize_text_field( $_POST['nome'][$w] );
              $safeCPF = sanitize_text_field( $_POST['cpf'][$w] );
              $safeCourse = sanitize_text_field( $_POST['curso'][$w] );
                            
              $countCoursesQuantity = count( $_POST['curso'] );
              if( $countCoursesQuantity > $this->coursesAndQuantity[$w]['quantity'] ){
                $msg = "<strong>Cadastros não realizados</strong> - Atenção, é permitido apenas " . $this->coursesAndQuantity[$w]['quantity'] . " vagas para o <strong>" . $this->coursesPurchasedArray[$w]['name'] . "</strong> você tentou cadastrar " . $countCoursesQuantity . "!";
                wc_print_notice( $msg, 'error' );                    
                break;                
              }                 
              $curuser = wc_create_new_customer($safeEmail, $safeName, $senha);
              if( is_wp_error( $curuser ) ){
                echo $curuser->get_error_message();
                echo "<p><strong>" . $safeName . " - " . $safeEmail . "</strong></p><br>";
              }else{
                update_user_meta( $curuser, 'cpf', $safeCPF );
                update_user_meta( $curuser, 'userParent', $this->userid );                
                //echo "<p><strong>Usuário: " . $_POST['nome'][$w] . " - " . $_POST['email'][$w] . " criado com sucesso, permissão para o curso: " . $_POST['curso'][$w] . "</strong></p>";
                echo "<p>Usuário(s) criado(s) com sucesso.</p>";
                $returnPermission = Sensei_Utils::user_start_course($curuser, $safeCourse); 
                $permalink = trailingslashit( get_home_url() ) . 'meus-cursos';                
                if($returnPermission){
                  $message = "Parabéns, você ganhou um curso! <br>";
                  $message .= "Seguem os dados:<br><br>";
                  $message .= "Login: {$safeEmail}<br>";
                  $message .= "Senha: {$senha}<br>";
                  $message .= "Curso: {$safeCourse}<br>";
                  $message .= "Acesse o endereço: {$permalink} e aproveite!<br>";  
                  $headers = array('Content-Type: text/html; charset=UTF-8');                              
                  wp_mail($safeEmail, 'Você ganhou um curso!', $message, $headers);
                }               
              }                             
            }
          }
          ?>
      </div>    
    <?php
    }

    /**
    * Store the id, name and product_id of the purchased courses in an array. 
    */
    private function woosei_getCoursesPurchased(){
      $coursesPurchased = new Sensei_Course();  
      $i=0;
      foreach($this->courses as $crp){        
        $itemCourse = $coursesPurchased->get_product_courses( $crp );                      
        foreach($itemCourse as $key => $value){            
            $this->coursesPurchasedArray[$i]['ID'] = $value->ID;
            $this->coursesPurchasedArray[$i]['name'] = $value->post_title;
            $this->coursesPurchasedArray[$i]['product_id'] = $crp;
            $i++;
        }
      }           
    }
}

new WooSei_WooCommerce_Sensei_Integration_admin();