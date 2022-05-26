<template>
  <div class="container w-full flex justify-end space-x-3">
    <a
      class="
        w-14
        h-14
        rounded-lg
        flex
        items-center
        justify-center
        bg-cyan-500
        shadow-lg
      "
      @click="goToPage(link.url)"
      v-for="(link, index) in links"
      :key="index"
      v-html="link.label"
    >
    </a>
  </div>
</template>

<script>
export default {
  props: {
    links: [],
  },
  data: function () {
    return {};
  },
  methods: {
    goToPage: function (url) {
      this.$emit("goToPage", url);
    },
  },
};
</script>

<style>
</style>
