<?php
/*
 * @copyright Copyright (c) 2021 AltumCode (https://altumcode.com/)
 *
 * This software is exclusively sold through https://altumcode.com/ by the AltumCode author.
 * Downloading this product from any other sources and running it without a proper license is illegal,
 *  except the official ones linked from https://altumcode.com/.
 */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Middlewares\Authentication;
use Altum\Middlewares\Csrf;

class Products extends Controller {

    public function index() {

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `products` WHERE `user_id` = {$this->user->user_id}")->fetch_object()->total ?? 0;

        /* Get the products list for the user */
        $products = [];
        $products_result = database()->query("SELECT * FROM `products` WHERE `user_id` = {$this->user->user_id}");
        while($row = $products_result->fetch_object()) $products[] = $row;

        /* Prepare the View */
        $data = [
            'products'            => $products,
            'total_products'      => $total_rows,
        ];



        $view = new \Altum\Views\View('products/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function delete() {

        Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete')) {
            Alerts::add_info(l('global.info_message.team_no_access'));
            redirect('pixels');
        }

        if(empty($_POST)) {
            redirect('pixels');
        }

        $pixel_id = (int) $_POST['pixel_id'];

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$pixel = db()->where('pixel_id', $pixel_id)->where('user_id', $this->user->user_id)->getOne('pixels', ['pixel_id', 'name'])) {
            redirect('pixels');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Delete the project */
            db()->where('pixel_id', $pixel_id)->delete('pixels');

            /* Clear the cache */
            \Altum\Cache::$adapter->deleteItemsByTag('pixels?user_id=' . $this->user->user_id);

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.delete1'), '<strong>' . $pixel->name . '</strong>'));

            redirect('pixels');
        }

        redirect('pixels');
    }

}
