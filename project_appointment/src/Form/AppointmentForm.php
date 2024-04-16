<?php

namespace Drupal\project_appointment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class AppointmentForm extends FormBase {

    protected $mailManager;
    protected $languageManager;

    public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager) {
        $this->mailManager = $mail_manager;
        $this->languageManager = $language_manager;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('plugin.manager.mail'),
            $container->get('language_manager')
        );
    }

    public function getFormId() {
        return 'appointment_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        // $form['#attached']['library'][] = 'project_appointment/appointment_calendar';

        if ($form_state->get('step') === NULL) {
            $form_state->set('step', 1);
        }

        switch ($form_state->get('step')) {
            case 1:
                $form['appointment_name'] = [
                    '#type' => 'textfield',
                    '#title' => $this->t('Appointment Name'),
                    '#description' => $this->t('Enter a name that descrives yor appointment.'),
                    '#required' => TRUE,
                    '#default_value' => $form_state->get('appointment_name'),
                ];
                $form['appointment_timezone'] = [
                    '#type' => 'select',
                    '#title' => $this->t('Select Your Timezone.'),
                    '#required' => TRUE,
                    '#options' => system_time_zones(), 
                    '#default_value' => $form_state->get('appointment_timezone'),
                ];
                $form['appointment_date'] = [
                    '#type' => 'datetime',
                    '#title' => $this->t('Select The Appointment Date And Time'),
                    '#required' => TRUE,
                    '#default_value' => $form_state->get('appointment_date'),
                ];
                $form['appointment_duration'] = [
                    '#type' => 'select',
                    '#title' => $this->t('How Long Do You Need?'),
                    '#required' => TRUE,
                    '#options' => [
                        '1800' => $this->t('30 minutes'),
                        '2700' => $this->t('45 minutes'),
                        '3600' => $this->t('1 hour'),
                    ],
                    '#default_value' => $form_state->get('appointment_duration'),
                ];
                $form['appointment_platform'] = [
                    '#type' => 'select',
                    '#title' => $this->t('Choose a platform'),
                    '#required' => TRUE,
                    '#options' => [
                        'Zoom' => $this->t('Zoom'),
                        'Teams' => $this->t('Teams'),
                        'Google_meet' => $this->t('Google meet'),
                        'Physical_meet' => $this->t('Physical Meet (Office)'),
                    ],
                    '#default_value' => $form_state->get('appointment_platform'),
                ];
                
                $form['project_details'] = [
                    '#type' => 'textarea',
                    '#title' => $this->t('Appointment Details'),
                    '#required' => TRUE,
                    '#default_value' => $form_state->get('project_details'),
                ];

                $form['name'] = [
                    '#type' => 'textfield',
                    '#title' => $this->t('Participants Name'),
                    '#description' => $this->t('Enter participants name separated by commas (If there are more then one).'),
                    '#required' => TRUE,
                    '#default_value' => $form_state->get('name'),
                ];

                $form['participants'] = [
                    '#type' => 'textfield',
                    '#title' => $this->t('Participants Email'),
                    '#description' => $this->t('Enter participant emails separated by commas.'),
                    '#required' => TRUE,
                    '#default_value' => $form_state->get('participants'),
                ];

                $form['actions']['next'] = [
                    '#type' => 'submit',
                    '#value' => $this->t('Submit'),
                    '#button_type' => 'primary',
                    '#submit' => ['::promptReview'],
                ];
                break;

            case 2:
                $timezone_name = system_time_zones()[$form_state->get('appointment_timezone')];
                $duration_minutes = $form_state->get('appointment_duration') / 60;
                $form['review'] = [
                    '#type' => 'markup',
                    '#markup' => $this->t('Please review the details below before confirming your appointment:<br>Name: @appointment_name<br>Appointment Date: @date<br>Timezone: @timezone<br>Duration: @duration minutes<br>Appointment Platform: @appointment_platform<br>Project Details: @details<br>Name: @name<br>Participants: @participants <br>', [
                        '@appointment_name' => $form_state->get('appointment_name'),
                        '@date' => $form_state->get('appointment_date')->format('Y-m-d H:i:s'),
                        '@timezone' => $timezone_name,
                        '@duration' => $duration_minutes,
                        '@appointment_platform' =>  $form_state->get('appointment_platform'),
                        '@details' => $form_state->get('project_details'),
                        '@name' => $form_state->get('name'),
                        '@participants' => $form_state->get('participants'),
                    ]),
                ];

                $form['actions']['back'] = [
                    '#type' => 'submit',
                    '#value' => $this->t('Back'),
                    '#submit' => ['::backToEdit'],
                    '#limit_validation_errors' => [],
                ];

                $form['actions']['submit'] = [
                    '#type' => 'submit',
                    '#value' => $this->t('Confirm Appointment'),
                    '#button_type' => 'primary',
                ];
                break;
        }

        return $form;
    }

    public function promptReview(array &$form, FormStateInterface $form_state) {
        foreach ($form_state->getValues() as $key => $value) {
            if (!in_array($key, ['op', 'form_build_id', 'form_token', 'form_id'])) {
                $form_state->set($key, $value);
            }
        }
        $form_state->set('step', 2);
        $form_state->setRebuild(TRUE);
    }

    public function backToEdit(array &$form, FormStateInterface $form_state) {
        $form_state->set('step', 1);
        $form_state->setRebuild(TRUE);
    }


    
    public function submitForm(array &$form, FormStateInterface $form_state) {
        if ($form_state->get('step') == 2) {
            $connection = \Drupal::database();
            $result = $connection->insert('project_appointment')
                ->fields([
                    'appointment_name' => $form_state->get('appointment_name'),
                    'appointment_date' => $form_state->get('appointment_date') instanceof DrupalDateTime ? $form_state->get('appointment_date')->format('Y-m-d H:i:s') : NULL,
                    'appointment_timezone' => $form_state->get('appointment_timezone'),
                    'appointment_duration' => $form_state->get('appointment_duration'),
                    'appointment_platform' => $form_state->get('appointment_platform'),
                    'project_details' => $form_state->get('project_details'),
                    'name' => $form_state->get('name'),
                    'participants' => $form_state->get('participants'),
                    'completed' => 0,
                ])
                ->execute();
    
            if ($result) {
                \Drupal::messenger()->addMessage($this->t('Your appointment has been scheduled.'));
    
                $participants = $form_state->get('participants');
                if (!empty($participants)) {
    
                    $emails = explode(',', $participants);
                    
                    foreach ($emails as $email) {
                      
                        $email = trim($email);
    
                        
                        $appointmentName = $form_state->get('appointment_name');
                        $appointmentDate = $form_state->get('appointment_date') instanceof DrupalDateTime ? $form_state->get('appointment_date')->format('Y-m-d H:i:s') : NULL;
                        project_appointment_send_confirmation_mail($email, $appointmentName, $appointmentDate);
                    }
                }
            } else {
                \Drupal::messenger()->addError($this->t('Failed to schedule your appointment.'));
            }
        }
    }
    
}
