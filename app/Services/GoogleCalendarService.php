<?php

namespace App\Services;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GoogleCalendarService
{
    private $client;

    public function __construct()
    {
        $this->client = $this->getGoogleClient();
    }

    /**
     * Check if the user is connected to Google Calendar
     */
    public function isConnected()
    {
        return Session::has('google_token');
    }

    /**
     * Get all events from the calendar
     */
    public function getEvents()
    {
        try {
            if (!$this->isConnected()) {
                return [];
            }

            $token = Session::get('google_token');
            $this->client->setAccessToken($token);

            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    $newToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());

                    if (isset($newToken['error'])) {
                        Log::error('Token refresh error: ' . ($newToken['error_description'] ?? $newToken['error']));
                        throw new \Exception('Failed to refresh token: ' . ($newToken['error_description'] ?? $newToken['error']));
                    }

                    Session::put('google_token', $this->client->getAccessToken());
                } else {
                    throw new \Exception('Your session has expired. Please reconnect your Google account.');
                }
            }

            $service = new Google_Service_Calendar($this->client);
            $calendarId = 'primary';

            $optParams = [
                'maxResults' => 10,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => date('c'),
            ];

            $results = $service->events->listEvents($calendarId, $optParams);
            $events = [];

            foreach ($results->getItems() as $event) {
                $start = $event->start->dateTime ?: $event->start->date;
                $end = $event->end->dateTime ?: $event->end->date;

                $events[] = [
                    'id' => $event->id,
                    'title' => $event->getSummary(),
                    'description' => $event->getDescription(),
                    'start' => $start,
                    'end' => $end,
                ];
            }

            return $events;
        } catch (\Exception $e) {
            Log::error('Event listing error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get events for a specific user
     */
    public function getUserEvents($userId)
    {
        // In a real application, you would filter events by user
        // For now, we'll just return all events since Google Calendar doesn't have built-in user filtering
        return $this->getEvents();
    }

    /**
     * Create a new event
     */
    public function createEvent($eventData)
    {
        try {
            if (!$this->isConnected()) {
                throw new \Exception('Not connected to Google Calendar');
            }

            $this->client->setAccessToken(Session::get('google_token'));

            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    Session::put('google_token', $this->client->getAccessToken());
                } else {
                    throw new \Exception('Your session has expired. Please reconnect your Google account.');
                }
            }

            $service = new Google_Service_Calendar($this->client);

            $event = new Google_Service_Calendar_Event([
                'summary' => $eventData['title'],
                'description' => $eventData['description'],
                'start' => [
                    'dateTime' => date('c', strtotime($eventData['start'])),
                    'timeZone' => 'Asia/Kathmandu',
                ],
                'end' => [
                    'dateTime' => date('c', strtotime($eventData['end'])),
                    'timeZone' => 'Asia/Kathmandu',
                ],
            ]);

            $calendarId = 'primary';
            return $service->events->insert($calendarId, $event);
        } catch (\Exception $e) {
            Log::error('Event creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete an event
     */
    public function deleteEvent($eventId)
    {
        try {
            if (!$this->isConnected()) {
                throw new \Exception('Not connected to Google Calendar');
            }

            $this->client->setAccessToken(Session::get('google_token'));

            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    Session::put('google_token', $this->client->getAccessToken());
                } else {
                    throw new \Exception('Your session has expired. Please reconnect your Google account.');
                }
            }

            $service = new Google_Service_Calendar($this->client);
            $calendarId = 'primary';

            return $service->events->delete($calendarId, $eventId);
        } catch (\Exception $e) {
            Log::error('Event deletion error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get auth URL for connecting to Google
     */
    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Handle the callback from Google OAuth
     */
    public function handleCallback($code)
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        // Check if there's an error in the token
        if (isset($token['error'])) {
            throw new \Exception('Authentication error: ' . ($token['error_description'] ?? $token['error']));
        }

        Session::put('google_token', $token);
        return true;
    }

    /**
     * Disconnect from Google Calendar
     */
    public function disconnect()
    {
        Session::forget('google_token');
        return true;
    }

    /**
     * Get the Google client
     */
    private function getGoogleClient()
    {
        try {
            $client = new Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setRedirectUri(config('services.google.redirect'));
            $client->addScope(Google_Service_Calendar::CALENDAR);
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            return $client;
        } catch (\Exception $e) {
            Log::error('Failed to create Google client: ' . $e->getMessage());
            throw $e;
        }
    }
}