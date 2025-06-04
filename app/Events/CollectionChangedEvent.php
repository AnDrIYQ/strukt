<?php

namespace App\Events;

use App\Models\Collection;
use App\Models\EndPoint;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CollectionChangedEvent implements ShouldBroadcastNow
{
    public string $action;
    public ?int $userId;

    public function __construct(
        public Collection $collection,
        string $action,
        public EndPoint $endpoint,
        ?int $userId = null,
        public array $payload = [],
    ) {
        $this->action = $action;
        $this->userId = $userId;
    }

    public function broadcastOn(): Channel
    {
        return new Channel("collection.{$this->collection->name}");
    }

    public function broadcastWhen(): bool
    {
        if ($this->endpoint->own_only) {
            return auth()->id() === $this->userId;
        }

        return true;
    }

    public function broadcastWith(): array
    {
        return [
            'collection' => $this->collection->name,
            'action' => $this->action,
            'payload' => $this->payload,
        ];
    }

    public function broadcastAs(): string
    {
        return 'collection.changed';
    }
}
