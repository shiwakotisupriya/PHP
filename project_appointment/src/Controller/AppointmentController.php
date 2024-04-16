<?php
namespace Drupal\project_appointment\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

class AppointmentController extends ControllerBase {

  public function viewAppointments() {
    $header = [
      $this->t('Appointment Name'),
      $this->t('Appointment Date'),
      $this->t('Duration (H:M:S)'), 
      $this->t('Timezone'),
      $this->t('Platform'),
      $this->t('Appointment Details'),
      $this->t('Participants Name'),
      $this->t('Participants Email'),
      $this->t('Completed'),
      ['data' => $this->t('Actions'), 'class' => ['responsive-priority-medium']],
    ];
    $rows = [];
    $query = \Drupal::database()->select('project_appointment', 'pa')
      ->fields('pa', [
        'id',
        'appointment_name', 
        'appointment_date', 
        'appointment_duration', 
        'appointment_timezone',
        'appointment_platform',
        'project_details', 
        'name', 
        'participants', 
        'completed'
      ])
      ->condition('completed', 0); // Add this line to filter out completed appointments

    $result = $query->execute();

    while ($appointment = $result->fetchAssoc()) {
      $completed_text = $appointment['completed'] ? $this->t('Yes') : $this->t('No');
      $durationFormatted = $this->formatDuration($appointment['appointment_duration']);

      $links = [];
      if (!$appointment['completed']) {
        $links['mark_completed'] = [
          'title' => $this->t('Mark as Completed'),
          'url' => Url::fromRoute('project_appointment.mark_completed', ['id' => $appointment['id']]),
        ];
      } else {
        $links['completed'] = [
          'title' => $this->t('Completed'),
          'url' => Url::fromRoute('<none>'),
          'attributes' => ['class' => ['is-completed-action']],
        ];
      }

      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('project_appointment.delete', ['id' => $appointment['id']]),
      ];

      $rows[] = [
        'data' => [
          $appointment['appointment_name'],
          $appointment['appointment_date'],
          $durationFormatted,
          $appointment['appointment_timezone'],
          $appointment['appointment_platform'],
          $appointment['project_details'],
          $appointment['name'],
          $appointment['participants'],
          $completed_text,
          [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ],
        ],
      ];
    }
    $appointmentsTableBuild = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No incomplete appointments found.'),
    ];
    $editorialCalendarBuild = $this->viewEditorialCalendar();
     $build = [
      'appointments_table' => $appointmentsTableBuild,
      'editorial_calendar' => [
        '#type' => 'markup',
        '#markup' => '<h2>Editorial Calendar</h2>', 
        '#allowed_tags' => ['h2'], 
      ],
      'calendar_view' => $editorialCalendarBuild, 

    ];
    return $build;
}

public function completedAppointments() {
  $header = [
    ['data' => $this->t('Appointment Name'), 'field' => 'appointment_name'],
    ['data' => $this->t('Appointment Date'), 'field' => 'appointment_date'],
    ['data' => $this->t('Duration (H:M:S)'), 'field' => 'appointment_duration'],
    ['data' => $this->t('Timezone'), 'field' => 'appointment_timezone'],
    ['data' => $this->t('Platform'), 'field' => 'appointment_platform'],
    ['data' => $this->t('Appointment Details'), 'field' => 'project_details'],
    ['data' => $this->t('Participants Name'), 'field' => 'name'],
    ['data' => $this->t('Participants Email'), 'field' => 'participants'],
    ['data' => $this->t('Action')],
  ];

  $query = \Drupal::database()->select('project_appointment', 'pa')
    ->fields('pa', [
      'id',
      'appointment_name', 
      'appointment_date', 
      'appointment_duration', 
      'appointment_timezone',
      'appointment_platform',
      'project_details', 
      'name', 
      'participants',
    ])
    ->condition('completed', 1); 

  $results = $query->execute();

  $rows = [];
  foreach ($results as $row) {
    $delete_url = Url::fromRoute('project_appointment.delete', ['id' => $row->id]);
    $delete_link = Link::fromTextAndUrl($this->t('Delete'), $delete_url)->toString();
    $durationFormatted = $this->formatDuration($row->appointment_duration);

    $rows[] = [
      'data' => [
        $row->appointment_name,
        $row->appointment_date,
        $durationFormatted,
        $row->appointment_timezone,
        $row->appointment_platform,
        $row->project_details,
        $row->name,
        $row->participants,
        $delete_link,
      ],
    ];
  }

  return [
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => $this->t('No completed appointments found.'),
  ];
}


  private function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
  }

  public function markCompleted($id) {
    $connection = \Drupal::database();
    $connection->update('project_appointment')
      ->fields(['completed' => 1])
      ->condition('id', $id)
      ->execute();

    \Drupal::logger('project_appointment')->notice('Appointment with ID @id marked as completed.', ['@id' => $id]);
    \Drupal::messenger()->addMessage($this->t('Appointment marked as completed.'));

    return new RedirectResponse(Url::fromRoute('project_appointment.admin_appointments')->toString());
  }

  
  public function delete($id) {
      $connection = \Drupal::database();
      $connection->delete('project_appointment')
          ->condition('id', $id)
          ->execute();
  
      $current_request = \Drupal::service('request_stack')->getCurrentRequest();
      $destination = $current_request->query->get('destination');
  
      \Drupal::messenger()->addMessage($this->t('Appointment has been deleted.'));
      if ($destination) {
          return new RedirectResponse($destination);
      } else {
          $referer = $current_request->headers->get('referer');
          return $referer ? new RedirectResponse($referer) : new RedirectResponse(Url::fromRoute('/appointment')->toString());
      }
  }
  


  public function viewEditorialCalendar() {
    $view_id = 'editorial_calendar';
    $display_id = 'page_1';
    $view = Views::getView($view_id);
    
    if (is_object($view)) {
      $view->setDisplay($display_id);
      $view->execute();
  
      $render_array = $view->buildRenderable($display_id, []);
      $render_array['#attached']['library'][] = 'project_appointment/appointment_calendar';
  
      return $render_array;
    }
  
    return [
      '#type' => 'markup',
      '#markup' => $this->t('The requested view could not be found.'),
      '#attached' => [
        'library' => [
          'project_appointment/appointment_calendar',
        ],
      ],
    ];
  }

public function getAppointmentsData() {
  $data = []; 
  $currentDateTime = new \DateTime();
  $currentDateTimeFormatted = $currentDateTime->format('Y-m-d H:i:s');

  $query = \Drupal::database()->select('project_appointment', 'pa')
    ->fields('pa', [
      'appointment_name', 
      'appointment_date',
      'appointment_duration',
      'appointment_timezone',
      'appointment_platform',
      'project_details',
      'name', 
      'participants', 
      'completed', 
    ])
    ->condition('appointment_date', $currentDateTimeFormatted, '>'); 

  $result = $query->execute();

  while ($appointment = $result->fetchAssoc()) {
    $durationFormatted = $this->formatDuration($appointment['appointment_duration']);
    $data[] = [
      'name' => $appointment['appointment_name'],
      'date' => $appointment['appointment_date'],
      'duration' => $durationFormatted,
      'timezone' => $appointment['appointment_timezone'],
      'platform' => $appointment['appointment_platform'],
      'project_details' => $appointment['project_details'],
      'participants' => $appointment['participants'], 
      'completed' => (bool) $appointment['completed'],
    ];
  }
  return new JsonResponse($data);
}


public function saveAppointment($appointmentData) {
  $connection = \Drupal::database();
  $result = $connection->insert('project_appointment')
              ->fields([
                  'appointment_name' => $appointmentData['name'],
                  'appointment_date' => $appointmentData['date'],
              ])
              ->execute();

  if ($result) {
    project_appointment_send_confirmation_mail($appointmentData['email'], $appointmentData['name'], $appointmentData['date']);
      \Drupal::messenger()->addMessage(\Drupal::translation()->translate('Appointment saved and confirmation email sent.'));
  } else {
      \Drupal::messenger()->addError(\Drupal::translation()->translate('Failed to save appointment.'));
  }
}









}
