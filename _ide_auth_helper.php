<?php

namespace Illuminate\Support\Facades {
    /**
     * @method static \App\Models\User|null user()
     */
    class Auth {}
}

namespace Illuminate\Contracts\Auth {
    /**
     * @method \App\Models\User|null user()
     */
    interface Guard {}
}
