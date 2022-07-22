<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\Flash;

/**
 * Profile controller
 *
 * PHP version 7.0
 */
class Workouts extends Authenticated
{

    /**
     * Before filter - called before each action method
     *
     * @return void
     */
    protected function before()
    {
        parent::before();

        $this->user = Auth::getUser();
    }

    /**
     * Show the profile
     *
     * @return void
     */
    public function newWorkoutAction()
    {
        View::renderTemplate('Workout/newWorkout.html', [
            'user' => $this->user
        ]);
    }

    /**
     * Update the profile
     *
     * @return void
     */
    public function addWorkoutAction()
    {
        if ($this->user->newWorkout($_POST)) {

            Flash::addMessage('Changes saved');

            View::renderTemplate('Workout/success.html', []);

            //$this->redirect('workout/success.html');

        } else {

            View::renderTemplate('Workout/newWorkout.html', []);

        }
    }

    public function workoutSessionAction()
    {
        View::renderTemplate('Workout/newWorkoutSession.html', [
            'user' => $this->user
        ]);
    }

    public function addWorkoutSessionAction()
    {
        if ($this->user->newWorkoutSession($_POST)) {

            Flash::addMessage('Changes saved');

            View::renderTemplate('Workout/success.html', []);

            //$this->redirect('workout/success.html');

        } else {

            View::renderTemplate('Workout/newWorkout.html', []);

        }
    }

    public function viewWorkoutsAction()
    {
        View::renderTemplate('Workout/viewWorkouts.html', [
            'user' => $this->user
        ]);
    }

    public function deleteWorkoutSessionAction() {
        $this->user->deleteLift($_POST);
        Flash::addMessage('delete action complete');
        View::renderTemplate('Workout/viewWorkouts.html', [
            'user' => $this->user
        ]);
    }

    public function editWorkoutSessionAction() {
        error_log($_POST['liftId']);
        //$this->user->updateLift($_POST);
        //Flash::addMessage('update action complete');
        View::renderTemplate('Workout/editWorkout.html', [
            'user' => $this->user
        ]);
    }
}
