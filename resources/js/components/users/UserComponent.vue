<template>
    <div>
        <div class="flex pb-1 justify-between items-center">
            <div class="flex items-center space-x-1 font-medium">
                <span class="iconify text-gray-700 dark:text-gray-400" data-icon="mdi:account-multiple"
                      data-inline="false"></span>
                <p class="transition-none text-gray-700 cursor-default dark:text-gray-400"
                   title="Hablar müəyyən mövzularda nəşrlərin yerləşdirildiyi bölmələrdir.">Müəlliflər</p>
            </div>
            <div
                class="ml-auto px-2 w-1/2 overflow-hidden mb-2 flex items-center justify-between dark:border-gray-700 xs:hidden">
                <div class="flex items-center cursor-pointer font-medium text-gray-700 dark:text-gray-400"
                     v-for="column in columns" :key="column.type"
                     @click="sortByColumn(column.type)">
                    <p class="dark:text-gray-300">{{ column.name }} </p>
                    <div :class="order === 'desc' && column.type === sortedColumn ? 'block' : 'hidden'">
                        <span class="iconify font-light dark:text-gray-300"
                              data-icon="bi:arrow-up"></span>
                    </div>
                    <div :class="order === 'asc' && column.type === sortedColumn ? 'block' : 'hidden'">
                        <span class="iconify font-light dark:text-gray-300"
                              data-icon="bi:arrow-down"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="pb-2 relative" id="hide">
            <input type="text" placeholder="Müəllif tapmaq" v-model="search"
                   class="input border rounded-none"
                   @keyup.enter="searchUnit">
            <span class="iconify absolute translate-y-1/2 top-0 my-3 mr-4 cursor-pointer right-0 dark:text-gray-400"
                  data-icon="mdi-magnify" @click="searchUnit"></span>
        </div>
        <users-loading v-if="loading"></users-loading>
        <div v-for="user in users" v-if="!loading && users"
             class="flex space-x-4 border mb-2 p-2 bg-white dark:bg-dpaper dark:border-gray-700" :id="user.id">
            <img :src="user.attributes.avatar"
                 class="w-16 h-16 rounded" alt="User profile">
            <div class="w-6/12">
                <a :href="'/@' + user.attributes.username">
                    <div class="font-semibold dark:text-gray-300">{{ user.attributes.name }}
                        {{ '@' + user.attributes.username }}
                    </div>
                    <div class="text-sm w-full pb-2 xs:text-xs dark:text-gray-300">{{ user.attributes.about }}</div>
                </a>
            </div>
            <div class="w-1/12 m-auto text-center xs:hidden">
                <div class="font-semibold dark:text-gray-300">{{ user.attributes.articles_count }}</div>
                <p class="text-sm uppercase dark:text-gray-300">Paylaşım</p>
            </div>
            <div class="w-1/12 m-auto text-center xs:hidden">
                <div class="font-semibold dark:text-gray-300">{{ user.attributes.karma }}</div>
                <p class="text-sm uppercase dark:text-gray-300">KARMA</p>
            </div>
            <div class="w-1/12 m-auto text-center xs:hidden">
                <div class="font-semibold dark:text-gray-300">{{ user.attributes.rating }}</div>
                <p class="text-sm uppercase dark:text-gray-300">REYTINQ</p>
            </div>
            <user-follow-button :id="user.id" :follow_check="user.attributes.follower"
                                class="w-2/12 m-auto text-center"></user-follow-button>
        </div>
        <div class="bg-white rounded border text-center grid space-x-2 py-24 dark:bg-dpaper dark:border-gray-700"
             v-if="!loading && users.length === 0">
            <span class="opacity-75" style="font-size: 5rem">
                <span class="iconify mx-auto text-red-700 dark:text-red-500" data-icon="el:remove-sign"
                      data-inline="false"></span>
            </span>
            <span class="opacity-75 text-3xl pt-2 dark:text-gray-300">Müəllif tapılmadı</span>
        </div>
        <pagination v-if="pagination.last_page > 1 && !loading" :pagination="pagination" :offset="5"
                    @paginate="getUsers()" style="margin: 0 auto">
        </pagination>
    </div>
</template>


<script>
import axios from 'axios'
import _ from "lodash";

export default {
    props: ['auth_check', 'columns', 'url'],
    data: function () {
        return {
            perPage: 10,
            sortedColumn: 'rating',
            order: 'desc',
            error: false,
            users: [],
            loading: false,
            search: '',
            pagination: {
                'current_page': 1
            }
        }
    },
    mounted: function () {
        this.getUsers();
    },
    methods: {
        searchUnit: _.debounce(function () {
            if (this.search.length > 2) {
                this.loading = true;
                this.hubsEmpty = false
                axios.get('/api/search_user?q=' + this.search)
                    .then(({data}) => {
                        this.loading = false;
                        this.pagination = data.meta
                        this.users = data.data
                        if (this.users.length === 0) {
                            this.hubsEmpty = true
                        }
                    }).catch(error => {
                    this.loading = false
                    this.error = true
                    if (error.response) {
                        console.log(error.response.data);
                        console.log(error.response.status);
                        console.log(error.response.headers);
                    } else if (error.request) {
                        console.log(error.request);
                    } else {
                        console.log('Error', error.message);
                    }
                });
            }
        }),
        async sortByColumn(column) {
            if (column === this.sortedColumn) {
                this.order = (this.order === 'asc') ? 'desc' : 'asc'
            } else {
                this.sortedColumn = column
                this.order = 'desc'
            }
            await this.getUsers()
        },
        async getUsers() {
            this.loading = true;
            await axios.get(this.url + `?page=${this.pagination.current_page}&column=${this.sortedColumn}&order=${this.order}&perPage=${this.perPage}`)
                .then(response => {
                    this.loading = false;
                    this.users = response.data.data;
                    if (response.data.meta) {
                        this.pagination = response.data.meta;
                        if (this.pagination.last_page > 50) {
                            this.pagination.last_page = 50;
                        }
                    }
                }).catch(error => {
                    this.loading = false;
                    this.error = true;
                    console.log(error);
                });
        }
    }

}
</script>

