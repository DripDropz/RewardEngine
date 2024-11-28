<script setup>
import {Head} from "@inertiajs/vue3";

defineProps({
    title: String,
});
</script>
<template>
    <v-app>
        <Head :title="title"/>
        <v-app-bar color="white" elevation="0" class="px-8">
            {{ title }}
        </v-app-bar>
        <v-main>
            <slot/>
        </v-main>
        <v-footer app>
            <v-container class="text-center text-sm-body-2">
                Powered by
                <a class="text-black" href="https://github.com/DripDropz/RewardEngine" target="_blank">DripDropz.io RewardEngine</a>
                {{ $page.props.appVersion }}
            </v-container>
        </v-footer>
    </v-app>
</template>
