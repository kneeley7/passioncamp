<?php

namespace App\Exports\Transformers;

use League\Fractal\TransformerAbstract;

class TicketTransformer extends TransformerAbstract
{
    private $includeAdditionalFields;

    protected $defaultIncludes = ['contact'];

    public function __construct($includeAdditionalFields = false)
    {
        $this->includeAdditionalFields = $includeAdditionalFields;
    }

    public function transform($ticket)
    {
        $data = [
            'id' => $ticket->id,
            'created at' => $ticket->created_at->toDateTimeString(),
            'updated at' => $ticket->updated_at->toDateTimeString(),
            'type' => $ticket->agegroup,
            'first name' => $ticket->person->first_name,
            'last name' => $ticket->person->last_name,
            'gender' => $ticket->person->gender,
            'grade' => $ticket->person->grade,
        ];

        $data += $ticket->person->formatted_considerations->toArray();

        if ($this->includeAdditionalFields) {
            $data += [
                'email' => $ticket->email,
                'phone' => $ticket->phone,
                'birthdate' => $ticket->birthdate ? $ticket->birthdate->toDateString() : null,
                'shirtsize' => $ticket->shirtsize,
                'school' => $ticket->school,
                'roommate requested' => $ticket->roommate_requested,
                'squad' => $ticket->squad,
                'leader' => $ticket->leader,
                'bus' => $ticket->bus,
                'travel plans' => $ticket->travel_plans,
                'price' => $ticket->price / 100,
            ];
        }

        return $data;
    }

    public function includeContact($ticket)
    {
        return $this->item($ticket->order->user->person, new ContactTransformer);
    }

    // public function includeGroup($ticket)
    // {
    //     return $this->item($ticket->groupTicket->group, new GroupTransformer);
    // }

    // public function includeContact($ticket)
    // {
    //     return $this->item($ticket->groupTicket->contact, new ContactTransformer);
    // }

    // public function includeInviter($ticket)
    // {
    //     return $this->item($ticket->groupTicket->inviter, new InviterTransformer);
    // }
}