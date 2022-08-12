<?php

namespace App\Controllers;

use \App\Models\User;

/**
 * Data testing for ajax calls 
 *
 * PHP version 7.0
 */
class DataTest extends \Core\Controller
{

    /**
     * Show the forgotten password page
     *
     * @return void
     */
    public function getData()
    {
      echo json_encode(User::getDates());
    }

}
