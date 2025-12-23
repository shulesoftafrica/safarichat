<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateSchemaNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:migrate-schema-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate from user UUID to instance UUID for schema names';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting schema name migration...');
        
        // Step 1: Generate UUIDs for all WhatsApp instances
        $instances = \App\Models\WhatsappInstance::whereNull('uuid')->get();
        
        if ($instances->count() > 0) {
            $this->info("Found {$instances->count()} instances without UUIDs. Generating...");
            
            foreach ($instances as $instance) {
                $instance->uuid = (string) \Illuminate\Support\Str::uuid();
                $instance->save();
                $this->info("Generated UUID for instance {$instance->id}: {$instance->uuid}");
            }
        } else {
            $this->info('All instances already have UUIDs.');
        }
        
        // Step 2: Update webhook configurations
        $this->updateWebhookConfigurations();
        
        // Step 3: Update any cached schema references
        $this->clearSchemaCache();
        
        $this->info('Schema name migration completed successfully!');
        
        // Step 4: Provide next steps
        $this->warn('IMPORTANT: After this migration:');
        $this->warn('1. Update your external WhatsApp API webhook configurations');
        $this->warn('2. Use the new /webhook/whatsapp/{instanceUuid} endpoint');
        $this->warn('3. Test webhook routing with the new UUID-based system');
        
        return 0;
    }
    
    /**
     * Update webhook configurations with new instance UUIDs
     */
    private function updateWebhookConfigurations()
    {
        $this->info('Updating webhook configurations...');
        
        // Update webhook URLs with new instance UUIDs
        $instances = \App\Models\WhatsappInstance::whereNotNull('uuid')->get();
        
        foreach ($instances as $instance) {
            $webhookUrl = config('app.url') . "/webhook/whatsapp/{$instance->uuid}";
            $instance->update(['webhook_url' => $webhookUrl]);
            $this->info("Updated webhook URL for instance {$instance->id}");
        }
    }
    
    /**
     * Clear schema cache
     */
    private function clearSchemaCache()
    {
        // Clear any Redis/cache entries using old user UUIDs
        \Illuminate\Support\Facades\Cache::flush();
        $this->info('Cleared schema cache');
    }
}
