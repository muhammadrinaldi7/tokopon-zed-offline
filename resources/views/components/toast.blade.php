<div x-data="toastComponent()" x-on:toast.window="addToast($event.detail)"
    class="fixed top-6 right-6 z-50 flex flex-col gap-3 pointer-events-none items-end">

    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.show" x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-8 scale-90"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-8 scale-90"
            class="pointer-events-auto rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-white/60 backdrop-blur-xl overflow-hidden max-w-sm w-full bg-white/80"
            :class="{
                'border-emerald-200': toast.type === 'success',
                'border-rose-200': toast.type === 'error',
                'border-amber-200': toast.type === 'warning',
                'border-blue-200': toast.type === 'info' || !toast.type
            }">

            <div class="p-4 flex gap-3.5 items-start relative overflow-hidden">
                <!-- Icon -->
                <div class="shrink-0 mt-0.5">
                    <template x-if="toast.type === 'success'">
                        <div class="p-1.5 bg-emerald-100 shadow-sm text-emerald-600 rounded-full">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <div class="p-1.5 bg-rose-100 shadow-sm text-rose-600 rounded-full">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'info' || !toast.type">
                        <div class="p-1.5 bg-blue-100 shadow-sm text-blue-600 rounded-full">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'warning'">
                        <div class="p-1.5 bg-amber-100 shadow-sm text-amber-600 rounded-full">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </template>
                </div>

                <!-- Message -->
                <div class="flex-1">
                    <p class="text-[15px] font-bold text-gray-800" x-text="toast.title" x-show="toast.title"></p>
                    <p class="text-[14px] text-gray-600 leading-tight" x-text="toast.message"
                        :class="{ 'mt-1': toast.title, 'font-medium mt-0.5': !toast.title }"></p>
                </div>

                <!-- Close Button -->
                <button @click="removeToast(toast.id)"
                    class="text-gray-400 hover:text-gray-700 shrink-0 transition-colors bg-gray-100/50 backdrop-blur-md hover:bg-gray-200/50 rounded-full p-1 mt-0.5 border border-white/50 shadow-sm focus:outline-none">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Progress Bar -->
                <div class="absolute bottom-0 left-0 h-1 bg-gray-200/50 w-full rounded-b-2xl overflow-hidden">
                    <div class="h-full transition-all ease-linear"
                        :class="{
                            'bg-emerald-500': toast.type === 'success',
                            'bg-rose-500': toast.type === 'error',
                            'bg-amber-500': toast.type === 'warning',
                            'bg-blue-500': toast.type === 'info' || !toast.type
                        }"
                        :style="'width: ' + toast.progress + '%'"></div>
                </div>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('toastComponent', () => ({
                toasts: [],
                addToast(detail) {
                    let id = Date.now();
                    // Livewire 3/4 detail is either an array (if multiple args passed to dispatch) or an object
                    // Usually $dispatch('toast', { title: 'xx', message: 'xx', type: 'xx' }) 
                    let data = Array.isArray(detail) ? detail[0] : detail;

                    let toast = {
                        id: id,
                        title: data.title || '',
                        message: data.message || '',
                        type: data.type || 'info', // success, error, warning, info
                        duration: data.duration || 3500,
                        show: false,
                        progress: 100,
                        interval: null
                    };

                    // Prevent duplicated toasts 
                    if (this.toasts.some(t => t.message === toast.message && t.title === toast.title))
                        return;

                    this.toasts.push(toast);

                    setTimeout(() => {
                        let t = this.toasts.find(t => t.id === id);
                        if (t) t.show = true;
                    }, 50);

                    let startTime = Date.now();
                    toast.interval = setInterval(() => {
                        let elapsed = Date.now() - startTime;
                        let remaining = toast.duration - elapsed;
                        let t = this.toasts.find(t => t.id === id);

                        if (t) {
                            t.progress = Math.max(0, (remaining / toast.duration) * 100);
                            if (remaining <= 0) {
                                clearInterval(toast.interval);
                                this.removeToast(id);
                            }
                        } else {
                            clearInterval(toast.interval);
                        }
                    }, 20);
                },
                removeToast(id) {
                    let toast = this.toasts.find(t => t.id === id);
                    if (toast) {
                        toast.show = false;
                        setTimeout(() => {
                            this.toasts = this.toasts.filter(t => t.id !== id);
                        }, 300); // Wait for leave transition
                    }
                }
            }))
        })
    </script>
</div>
