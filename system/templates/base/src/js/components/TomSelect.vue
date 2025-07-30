<script setup>
/*
import TomSelect from "@/js/components/TomSelect.vue";

const selectRef = useTemplateRef("selectRef");
// can use `selectRef.select` to control the inner TomSelect object. i.e. `selectRef.select.clear()`.
// see TomSelect documentation

const tomSelectSettings = {
	// ... see TomSelect documentation
	// load: (query, sanitise) => return [{ value: "hello" }, ...]
}

// this will have our selected option
const selectedValue = defineModel();

<TomSelect ref="selectRef" v-model="selectedValue" :settings="tomSelectSettings">
	<!--
		if you don't want to use select settings to set options,
		you can use HTML <option> and <optgroup> here instead.
		you can also use vue to render the options as well, if preferred
	-->
</TomSelect>
 */

import {
	ref,
	onMounted,
	onUpdated,
} from "vue";

/* @ts-ignore */
import TomSelect from "~/tom-select";

// const emit = defineEmits(['change','input'])
const props = defineProps(["modelValue", "settings"]);
const el = ref(null);

const select = ref(null);

onMounted(() => {
	select.value = new TomSelect(el.value, props.settings);
});
// the options are empty in onMounted. probably cause of the slots usage thing. so we sync onUpdated
onUpdated(() => {
	select.value.sync();
});

defineExpose({
	select
});

</script>


<template>
	<select ref="el" @input="$emit('update:modelValue', $event.target.value)" v-bind="$attrs" :value="props.modelValue">
		<slot></slot>
	</select>
</template>