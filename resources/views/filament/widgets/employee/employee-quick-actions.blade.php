<x-filament-widgets::widget>
    <x-filament::section heading="Quick Actions">
        <div class="flex flex-wrap gap-3">
            <x-filament::button
                tag="a"
                href="{{ $this->logTimeUrl() }}"
                icon="heroicon-o-clock"
            >
                Log Time
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ $this->tasksUrl() }}"
                color="gray"
                icon="heroicon-o-clipboard-document-list"
            >
                View My Tasks
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ $this->timeEntriesUrl() }}"
                color="gray"
                icon="heroicon-o-document-text"
            >
                My Time Entries
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>