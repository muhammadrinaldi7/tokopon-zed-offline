<?php

namespace App\Livewire\Admin\AiAssistant;

use App\Models\AiSetting;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Settings extends Component
{
    public $provider;
    public $model;
    public $system_prompt;

    public function mount()
    {
        $setting = AiSetting::first();
        if ($setting) {
            $this->provider = $setting->provider ?? 'gemini';
            $this->model = $setting->model;
            $this->system_prompt = $setting->system_prompt;
        } else {
            $this->provider = 'gemini';
            $this->model = 'gemini-3.5-flash';
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
            'model' => 'required|string',
            'system_prompt' => 'required|string',
        ]);

        $setting = AiSetting::first();
        if (!$setting) {
            $setting = new AiSetting();
        }

        $setting->provider = $this->provider;
        $setting->model = $this->model;
        $setting->system_prompt = $this->system_prompt;
        $setting->save();

        session()->flash('success', 'Pengaturan AI berhasil diperbarui.');
    }
}
