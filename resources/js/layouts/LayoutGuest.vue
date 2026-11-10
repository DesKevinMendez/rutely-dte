<template>
    <div class="min-h-dvh bg-gray-50 dark:bg-gray-900">
        <main
            class="flex min-h-dvh flex-col items-center justify-center gap-8 px-4 py-8"
        >
            <div class="text-center">
                <img
                    src="/logo.png"
                    alt="Logo de Rutely DTE"
                    class="mx-auto mb-3 h-14 w-14 object-contain"
                />
                <p class="text-xl font-bold text-gray-900 dark:text-white">
                    Rutely DTE
                </p>
            </div>

            <div
                class="relative z-10 flex w-full justify-center text-base text-gray-900 dark:text-gray-100"
            >
                <RouterView />
            </div>
        </main>
    </div>
</template>
