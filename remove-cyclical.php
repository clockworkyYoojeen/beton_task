<?php
/*
  Plugin Name: Remove Cyclical Links Plugin (Убирает циклические ссылки из меню)
  Description:  Заменяет тег 'a' на тег 'span' в тех пунктах меню, которые указывают на отображаемую страницу
  Version:      20160911
  Author:       Yoojeen
  License:      GPL2
  License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) or die( 'No monkey business!!!' ); // защита от запуска файла напрямую в строке браузера

function rmc_admin_menu_option() {
	// создаём заголовок в админке слева, и страницу настроек плагина
	add_menu_page(
	'RMC Options page', // заголовок (в тегах title)
    'RMC Options', // что показывать в меню админки слева
    'manage_options', // доступ для всех
    'rmc-admin-menu', // slug
    'rmc_settings_page', // функция вывода страницы настроек плагина
    'dashicons-admin-generic', // иконка в админ панели
    123 // позиция в меню
	);
}

        // класс "обходчик" пунктов меню
        class Change_Links_Walker extends Walker_Nav_Menu {
			
            function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
        
            global $post;
        
            $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
        
            $class_names = $value = '';
        
            $classes = empty( $item->classes ) ? array() : (array) $item->classes;
        
            $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );
            $class_names = ' class="'. esc_attr( $class_names ) . '"';
        
            $output .= $indent . '<li id="menu-item-'. $item->ID . '"' . $value . $class_names .'>';
        
            $attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
            $attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
            $attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
            $attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';
        
            // при условии меняем html пункта меню
            $query_string = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	    $query_stringS = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            if( ($item->url === $query_string) ||  ($item->url === $query_stringS) ) {
                $item_output = '<span>' . $item->title . '</span>';
            }
            // иначе выводим ссылкой как обычно
             else {
            $item_output = $args->before;
            $item_output .= '<a'. $attributes .'>';
            $item_output .= $args->link_before .apply_filters( 'the_title', $item->title, $item->ID );
        
            $item_output .= '</a>';
            $item_output .= $args->after;
            } 
        
            $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
            }
        }

// навешиваем на событие формирования админки нашу функцию rmc_admin_menu_option
add_action( 'admin_menu', 'rmc_admin_menu_option' );

// массив с настройками нужен в глобальной области видимости
$rmc_menus = get_option( 'rmc_menus' );

// эта функция сформирует страницу настроек плагина
function rmc_settings_page() {
     ?>
    <div class="wrap">
        <h2>RMC Options Page</h2> 
        <p>Для корректной работы плагина необходимо при вызове меню в коде Вашей темы
        указывать ID этого меню. Например:
        <pre> 
        wp_nav_menu( array(
            'menu_class'     => 'nav-menu',
            'theme_location' => 'primary',
            // добавляем строку ниже (в данном случае ID = 2)
            'menu' => 2 
        ) );
        </pre>
Чтобы узнать ID меню в админ панели выберите нужное меню, и на странице его редактирования
спуститесь вниз, наведите мышь на ссылку "Удалить меню". В левом нижнем углу эрана появится
строка с информацией об этом меню, в ней найдите параметр &menu=5 (число 5 для примера).
Это число и будет ID этого меню.

        </p>      
        <?php
        // если отправили форму, получаем id выбранных меню
        if ( $_POST['submit_menus'] ) {
        $menu_ids = $_POST['rmc_menus'];
        // обновляем опции в базе данных
        update_option( 'rmc_menus', $menu_ids );
        }

        // если ни одно меню ещё не выбрано
        if ( !get_option( 'rmc_menus' ) ) {
            echo '<h3>Вы пока не выбрали ни одно меню</h3>';
        }

        global $rmc_menus;
        $rmc_menus = get_option( 'rmc_menus' );

        echo '<h4>Выберите меню чтобы избавиться от циклических ссылок в нём</h4>';
        // получаем данные о всех меню сайта в массив
        $menusArr = wp_get_nav_menus();
        //echo '<pre>'; print_r( $menusArr ); echo '</pre>';
        ?>
        
        <form action="<?php get_template_directory_uri() . "/wp-admin/admin.php"; ?>" method="post">
        <?php
        // идём по массиву, формируем чекбоксы
        foreach ( $menusArr as $menu ) {
             ?>
            <label><input type="checkbox" name="rmc_menus[]"
             <?php echo in_array($menu->term_id, $rmc_menus) ?  "checked" : ""; ?>
             value=<?php echo $menu->term_id; ?>>
            <?php echo $menu->name; ?></label><br>
        <?php
     }
      ?>
        <br>
        <input type="submit" class="button button-primary" name="submit_menus" value="update">
        </form>
        <?php
         }

// добавляем фильтр
add_filter( 'wp_nav_menu_args', 'set_walker' );

function set_walker( $args ) {
    global $rmc_menus;
    // устанавливаем walker для выбранных меню
    if ( in_array($args['menu'], $rmc_menus ) ) {
        $args['walker'] = new Change_Links_Walker();
    }
    return $args;
    }
        ?>
