<?php

return [
    'navigation' => [
        'customer_management' => 'Customer Management',
    ],

    'resources' => [
        'bookings' => [
            'label' => 'Booking',
            'plural_label' => 'Bookings',
            'navigation_label' => 'Bookings',
        ],
        'customers' => [
            'label' => 'Customer',
            'plural_label' => 'Customers',
            'navigation_label' => 'Customers',
        ],
        'events' => [
            'label' => 'Event',
            'plural_label' => 'Events',
            'navigation_label' => 'Events',
        ],
    ],

    'actions' => [
        'approve' => 'Approve',
        'cancel' => 'Cancel',
        'reject' => 'Reject',
    ],

    'event_types' => [
        'camp' => 'Camp',
        'festival' => 'Festival',
        'trip' => 'Trip',
    ],

    'fields' => [
        'bookings' => 'Bookings',
        'contract_terms' => 'Contract terms',
        'created_at' => 'Created at',
        'customer' => 'Customer',
        'description' => 'Description',
        'email_address' => 'Email address',
        'email_verified_at' => 'Email verified at',
        'ends_at' => 'Ends at',
        'event' => 'Event',
        'excerpt' => 'Excerpt',
        'family' => 'Family',
        'family_members' => 'Family members',
        'location' => 'Location',
        'maximum_age' => 'Maximum age',
        'minimum_age' => 'Minimum age',
        'name' => 'Name',
        'password' => 'Password',
        'phone_number' => 'Phone number',
        'reference' => 'Reference',
        'review_notes' => 'Review notes',
        'seat_capacity' => 'Seat capacity',
        'seats' => 'Seats',
        'slug' => 'Slug',
        'starts_at' => 'Starts at',
        'state' => 'State',
        'status' => 'Status',
        'type' => 'Type',
        'updated_at' => 'Updated at',
    ],

    'statuses' => [
        'archived' => 'Archived',
        'approved' => 'Approved',
        'awaiting_signature' => 'Awaiting signature',
        'cancelled' => 'Cancelled',
        'draft' => 'Draft',
        'pending_review' => 'Pending review',
        'published' => 'Published',
        'rejected' => 'Rejected',
        'signed' => 'Signed',
        'voided' => 'Voided',
    ],
];
