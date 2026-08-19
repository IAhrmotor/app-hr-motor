<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use App\Models\Contact;
use App\Models\ContentActivityLog;
use App\Models\User;
use App\Services\ContentActivityLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    protected function afterCreate(): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        /** @var Contact $contact */
        $contact = $this->record;

        app(ContentActivityLogger::class)->record(
            actor: $actor,
            contentType: ContentActivityLog::CONTENT_TYPE_CONTACT,
            action: ContentActivityLog::ACTION_CREATED,
            targetName: $contact->name,
            targetReference: $contact->enreach_extension ?: $contact->phone,
            changes: [
                'name' => ['from' => null, 'to' => $contact->name],
                'email' => ['from' => null, 'to' => $contact->email],
                'phone' => ['from' => null, 'to' => $contact->phone],
                'enreach_extension' => ['from' => null, 'to' => $contact->enreach_extension],
            ],
        );
    }
}
