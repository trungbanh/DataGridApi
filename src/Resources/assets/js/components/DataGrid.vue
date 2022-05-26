<template>
  <div class="container mx-auto">
    <table
      class="
        w-full
        m-0
        border-separate border-collapse border border-slate-700
        table-auto
      "
    >
      <thead>
        <tr class="w-full bg-zinc-400">
          <th
            v-for="(col, index) in columns"
            @click="sortBy(col.index)"
            :key="index"
            class="border border-slate-700 bg-zinc-400"
          >
            {{ col.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in dataTable" :key="index">
          <td
            class="border border-slate-300"
            v-for="(detail, rowIdx) in row"
            :key="rowIdx"
          >
            {{ detail }}
          </td>
        </tr>
      </tbody>
    </table>
    <Paginater :links="pages" @goToPage="goToPage"></Paginater>
  </div>
</template>

<script>
import Paginater from "./Paginater.vue";

export default {
  props: {},
  components: { Paginater },
  data: function () {
    return {
      responseData: {},
      dataTable: [],
      columns: [],
      pages: [],
    };
  },
  created: function () {},
  mounted: function () {
    this.syncData();
  },
  updated: function () {},
  destroyed: function () {},
  methods: {
    syncData: function () {
      axios.get("http://localhost:8000/api/users").then((response) => {
        this.responseData = response.data;
        this.columns = response.data.columns;
        this.dataTable = response.data.data.data;
        this.pages = response.data.data.links;
      });
    },
    sortBy: function (col) {
      console.log(col);
    },
    goToPage: (url) => {
      console.log(url);
    },
  },
};
</script>

<style>
</style>
