<?php

namespace App\Livewire\Admin\AiAssistant;

use App\Models\AiSetting;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Settings extends Component
{
    public $webhook_url;
    public $webhook_token;
    public $system_prompt;

    public function mount()
    {
        $setting = AiSetting::first();
        if ($setting) {
            $this->webhook_url = $setting->api_key;
            $this->webhook_token = $setting->model;
            $this->system_prompt = $setting->system_prompt;
        } else {
            $this->webhook_url = '';
            $this->webhook_token = '';
            $this->system_prompt = "Anda adalah Agen AI Analis Database profesional untuk sistem aplikasi Tokopon. Tugas Anda adalah menjawab pertanyaan user dengan cara menganalisis database secara akurat.";
        }
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.admin.ai-assistant.settings');
    }

    public function saveSettings()
    {
        $this->validate([
            'webhook_url' => 'required|url',
            'webhook_token' => 'required|string',
            'system_prompt' => 'required|string',
        ]);

        $setting = AiSetting::first();
        if (!$setting) {
            $setting = new AiSetting();
        }

        $setting->provider = 'n8n'; // hardcode to n8n since it's the only one used
        $setting->api_key = $this->webhook_url;
        $setting->model = $this->webhook_token;
        $setting->system_prompt = $this->system_prompt;
        $setting->save();

        session()->flash('success', 'Pengaturan AI berhasil diperbarui.');
    }
}
