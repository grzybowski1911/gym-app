<?php

namespace App\Models;

use PDO;
use \App\Token;
use \App\Mail;
use \Core\View;

/**
 * User model
 *
 * PHP version 7.0
 */
class User extends \Core\Model
{

    /**
     * Error messages
     *
     * @var array
     */
    public $errors = [];

    /**
     * Class constructor
     *
     * @param array $data  Initial property values (optional)
     *
     * @return void
     */
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        };
    }

    /**
     * Save the user model with the current property values
     *
     * @return boolean  True if the user was saved, false otherwise
     */
    public function save()
    {
        $this->validate();

        if (empty($this->errors)) {

            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            $token = new Token();
            $hashed_token = $token->getHash();
            $this->activation_token = $token->getValue();

            $sql = 'INSERT INTO users (name, email, password_hash, activation_hash)
                    VALUES (:name, :email, :password_hash, :activation_hash)';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
            $stmt->bindValue(':activation_hash', $hashed_token, PDO::PARAM_STR);

            return $stmt->execute();
        }

        return false;
    }

    /**
     * Validate current property values, adding valiation error messages to the errors array property
     *
     * @return void
     */
    public function validate()
    {
        // Name
        if ($this->name == '') {
            $this->errors[] = 'Name is required';
        }

        // email address
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[] = 'Invalid email';
        }
        if (static::emailExists($this->email, $this->id ?? null)) {
            $this->errors[] = 'email already taken';
        }

        // Password
        if (isset($this->password)) {

            if (strlen($this->password) < 6) {
                $this->errors[] = 'Please enter at least 6 characters for the password';
            }

            if (preg_match('/.*[a-z]+.*/i', $this->password) == 0) {
                $this->errors[] = 'Password needs at least one letter';
            }

            if (preg_match('/.*\d+.*/i', $this->password) == 0) {
                $this->errors[] = 'Password needs at least one number';
            }

        }
    }

    /**
     * See if a user record already exists with the specified email
     *
     * @param string $email email address to search for
     * @param string $ignore_id Return false anyway if the record found has this ID
     *
     * @return boolean  True if a record already exists with the specified email, false otherwise
     */
    public static function emailExists($email, $ignore_id = null)
    {
        $user = static::findByEmail($email);

        if ($user) {
            if ($user->id != $ignore_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find a user model by email address
     *
     * @param string $email email address to search for
     *
     * @return mixed User object if found, false otherwise
     */
    public static function findByEmail($email)
    {
        $sql = 'SELECT * FROM users WHERE email = :email';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     //* Authenticate a user by email and password. *
     * Authenticate a user by email and password. User account has to be active.
     *
     * @param string $email email address
     * @param string $password password
     *
     * @return mixed  The user object or false if authentication fails
     */
    public static function authenticate($email, $password)
    {
        $user = static::findByEmail($email);

        //if ($user) {
        if ($user && $user->is_active) {
            if (password_verify($password, $user->password_hash)) {
                return $user;
            }
        }

        return false;
    }

    /**
     * Find a user model by ID
     *
     * @param string $id The user ID
     *
     * @return mixed User object if found, false otherwise
     */
    public static function findByID($id)
    {
        $sql = 'SELECT * FROM users WHERE id = :id';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Remember the login by inserting a new unique token into the remembered_logins table
     * for this user record
     *
     * @return boolean  True if the login was remembered successfully, false otherwise
     */
    public function rememberLogin()
    {
        $token = new Token();
        $hashed_token = $token->getHash();
        $this->remember_token = $token->getValue();

        $this->expiry_timestamp = time() + 60 * 60 * 24 * 30;  // 30 days from now

        $sql = 'INSERT INTO remembered_logins (token_hash, user_id, expires_at)
                VALUES (:token_hash, :user_id, :expires_at)';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $this->expiry_timestamp), PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Send password reset instructions to the user specified
     *
     * @param string $email The email address
     *
     * @return void
     */
    public static function sendPasswordReset($email)
    {
        $user = static::findByEmail($email);

        if ($user) {

            if ($user->startPasswordReset()) {

                $user->sendPasswordResetEmail();

            }
        }
    }

    /**
     * Start the password reset process by generating a new token and expiry
     *
     * @return void
     */
    protected function startPasswordReset()
    {
        $token = new Token();
        $hashed_token = $token->getHash();
        $this->password_reset_token = $token->getValue();

        $expiry_timestamp = time() + 60 * 60 * 2;  // 2 hours from now

        $sql = 'UPDATE users
                SET password_reset_hash = :token_hash,
                    password_reset_expires_at = :expires_at
                WHERE id = :id';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
        $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $expiry_timestamp), PDO::PARAM_STR);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Send password reset instructions in an email to the user
     *
     * @return void
     */
    protected function sendPasswordResetEmail()
    {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/password/reset/' . $this->password_reset_token;

        $text = View::getTemplate('Password/reset_email.txt', ['url' => $url]);
        $html = View::getTemplate('Password/reset_email.html', ['url' => $url]);

        Mail::send($this->email, 'Password reset', $text, $html);
    }

    /**
     * Find a user model by password reset token and expiry
     *
     * @param string $token Password reset token sent to user
     *
     * @return mixed User object if found and the token hasn't expired, null otherwise
     */
    public static function findByPasswordReset($token)
    {
        $token = new Token($token);
        $hashed_token = $token->getHash();

        $sql = 'SELECT * FROM users
                WHERE password_reset_hash = :token_hash';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        $user = $stmt->fetch();

        if ($user) {
            
            // Check password reset token hasn't expired
            if (strtotime($user->password_reset_expires_at) > time()) {

                return $user;
            }
        }
    }

    /**
     * Reset the password
     *
     * @param string $password The new password
     *
     * @return boolean  True if the password was updated successfully, false otherwise
     */
    public function resetPassword($password)
    {
        $this->password = $password;

        $this->validate();

        //return empty($this->errors);
        if (empty($this->errors)) {

            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            $sql = 'UPDATE users
                    SET password_hash = :password_hash,
                        password_reset_hash = NULL,
                        password_reset_expires_at = NULL
                    WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
                                                  
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
                                          
            return $stmt->execute();
        }

        return false;
    }

    /**
     * Send an email to the user containing the activation link
     *
     * @return void
     */
    public function sendActivationEmail()
    {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/signup/activate/' . $this->activation_token;

        $text = View::getTemplate('Signup/activation_email.txt', ['url' => $url]);
        $html = View::getTemplate('Signup/activation_email.html', ['url' => $url]);

        Mail::send($this->email, 'Account activation', $text, $html);
    }

    /**
     * Activate the user account with the specified activation token
     *
     * @param string $value Activation token from the URL
     *
     * @return void
     */
    public static function activate($value)
    {
        $token = new Token($value);
        $hashed_token = $token->getHash();

        $sql = 'UPDATE users
                SET is_active = 1,
                    activation_hash = null
                WHERE activation_hash = :hashed_token';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':hashed_token', $hashed_token, PDO::PARAM_STR);

        $stmt->execute();
    }
    
    /**
     * Update the user's profile
     *
     * @param array $data Data from the edit profile form
     *
     * @return boolean  True if the data was updated, false otherwise
     */
    public function updateProfile($data)
    {
        $this->name = $data['name'];
        $this->email = $data['email'];

        // Only validate and update the password if a value provided
        if ($data['password'] != '') {
            $this->password = $data['password'];
        }

        $this->validate();

        if (empty($this->errors)) {

            $sql = 'UPDATE users
                    SET name = :name,
                        email = :email';

            // Add password if it's set
            if (isset($this->password)) {
                $sql .= ', password_hash = :password_hash';
            }

            $sql .= "\nWHERE id = :id";


            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

            // Add password if it's set
            if (isset($this->password)) {

                $password_hash = password_hash($this->password, PASSWORD_DEFAULT);
                $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);

            }

            return $stmt->execute();
        }

        return false;
    }

    public function NewWorkout($data) {

        $newLift = strtolower(str_replace(' ', '' ,$this->liftname = $data['lift']));

        error_log($newLift);

        //error_log('new work out action');

        $sql = 'CREATE TABLE ' . $newLift . '(
            Date 
            Reps varchar(255),
            Sets varchar(255),
            Weight varchar(255)
        )';

        //$sql = 'CREATE TABLE :liftname (Reps varchar(255), Sets varchar(255), Pounds varchar(255))';
        $db = static::getDB();
        $stmt = $db->prepare($sql);
        //$stmt->bindValue(':liftname', $this->liftname, PDO::PARAM_STR);
        return $stmt->execute();

    }

    public function NewWorkoutSession($data) {

        //$newLift = strtolower(str_replace(' ', '' ,$this->liftname = $data['lift']));

        //error_log($newLift);

        //error_log('new work out action');

        $this->user = $data['user'];
        $this->category = $data['category'];
        $this->liftname = $data['lift'];
        $this->weight = $data['weight'];
        $this->reps = $data['reps'];
        $this->sets = $data['sets'];

        //$sql = 'INSERT INTO remembered_logins (token_hash, user_id, expires_at)
        //        VALUES (:token_hash, :user_id, :expires_at)';

        $sql = 'INSERT INTO lift_sessions (user, category, lift_name, weight, reps, sets)
        VALUES (:user, :category , :liftname, :weight, :reps, :sets)';

        //$sql = 'CREATE TABLE ' . $newLift . '(
        //   Reps varchar(255),
        //    Sets varchar(255),
        //    Weight varchar(255)
        //)';

        //$sql = 'CREATE TABLE :liftname (Reps varchar(255), Sets varchar(255), Pounds varchar(255))';
        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user', $this->user, PDO::PARAM_STR);
        $stmt->bindValue(':category', $this->category, PDO::PARAM_STR);
        $stmt->bindValue(':liftname', $this->liftname, PDO::PARAM_STR);
        $stmt->bindValue(':weight', $this->weight, PDO::PARAM_INT);
        $stmt->bindValue(':reps', $this->reps, PDO::PARAM_INT);
        $stmt->bindValue(':sets', $this->sets, PDO::PARAM_INT);
        return $stmt->execute();

    }

    public static function getLifts() {
        //$sql = 'SELECT lift_name FROM lift_sessions';

        if(isset($_SESSION['user_id'])) {
            $sql = 'SELECT * FROM `lift_sessions` WHERE user = :user';
            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user', $_SESSION['user_id']);
            $stmt->execute();
            //error_log(print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return;
    }

    public static function compareWeeklyStats() {

        $current_date = date('F, jS');

        $lastWeek = date("Y-m-d", strtotime("-7 days"));

        if(isset($_SESSION['user_id'])) { 
            $sql = 'SELECT * FROM `lift_sessions` WHERE `date` LIKE CONCAT(:lastweek, "%") AND `user` =:user';
            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user', $_SESSION['user_id']);
            $stmt->bindValue(':lastweek', $lastWeek);
            $stmt->execute();
            //error_log(print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return;
    }

    public function deleteLift($data) {

        $this->liftId = $data['id'];

        if(isset($_SESSION['user_id'])) { 
            //error_log($this->liftId);
            $sql = 'DELETE FROM lift_sessions WHERE id =:id';
            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $this->liftId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function updateLift($data) {
        //$this->liftId = $data['liftId'];

        $fields = [];

        //$fields['user'] = [$data['user'], PDO::PARAM_INT];
        //$fields['liftId'] = [$data['liftId'], PDO::PARAM_INT];

        if(! empty($data['category'])) {
            $fields['category'] = [ $data['category'], PDO::PARAM_STR ];
        }

        if(! empty($data['lift'])) {
            $fields['lift'] = [ $data['lift'], PDO::PARAM_STR ];
        }

        if(! empty($data['weight'])) {
            $fields['weight'] = [ $data['weight'], PDO::PARAM_STR ];
        }

        if(! empty($data['reps'])) {
            $fields['reps'] = [ $data['reps'], PDO::PARAM_STR ];
        }

        if(! empty($data['sets'])) {
            $fields['sets'] = [ $data['sets'], PDO::PARAM_STR ];
        }

        $sets = array_map(function($val) {
            return "$val = :$val";
        }, array_keys($fields));

        $sql = "UPDATE lift_sessions SET " . implode(", ", $sets) . " WHERE user = :user AND id = :liftId";

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':user', $data['user']);
        $stmt->bindValue(':liftId', $data['liftId']);

        foreach($fields as $name => $values) {
            $stmt->bindValue(":$name", $values[0], $values[1]);
        }

        $stmt->execute();
            
        return $stmt->rowCount();

        //error_log(print_r($sets, true));
    }

    // add lift category to each lift so it can searched for based on what body part is being lfited
    // back, chest, shoulders, legs, arms
    public static function searchByLiftCat() {
        if(isset($_SESSION['user_id']) && isset($_POST['category']) ) { 
            $sql = 'SELECT * FROM `lift_sessions` WHERE `category` = :category  AND `user` =:user';
            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user', $_SESSION['user_id']);
            $stmt->bindValue(':category', $_POST['category']);
            $stmt->execute();
            //error_log(print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        //error_log($data['category']);
    }

    public static function getDates() {
        $sql = 'SELECT date FROM lift_sessions';
        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //error_log(print_r($dates, true));
        $new_dates = [];
        $regex = '/[0-9]{4}-[0-9]{2}-[0-9]{2}/i';
        foreach($dates as $date) {
            $clean = preg_match($regex, $date['date'], $m);
            array_push($new_dates, $m[0]);
        }
        return array_unique($new_dates);
    }
}
