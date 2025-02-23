import axios, {
    AxiosError,
    AxiosRequestTransformer,
    AxiosResponse,
    AxiosResponseTransformer,
    CreateAxiosDefaults,
} from 'axios'
import { camelizeKeys, decamelizeKeys } from 'humps'
import { z } from 'zod'
import { toast } from 'sonner'
import { UseQueryResult } from '@tanstack/react-query'

axios.defaults.withCredentials = true
axios.defaults.withXSRFToken = true

export const baseURL = import.meta.env.DEV ? 'http://localhost' : ''
axios.get(`${baseURL}/sanctum/csrf-cookie`)

export const axiosDefaults: CreateAxiosDefaults = {
    baseURL,
    transformRequest: [
        (data) => decamelizeKeys(data),
        ...(axios.defaults.transformRequest as AxiosRequestTransformer[]),
    ],
    transformResponse: [
        ...(axios.defaults.transformResponse as AxiosResponseTransformer[]),
        (data) => camelizeKeys(data),
    ],
    headers: {
        'Content-Type': 'application/json',
    },
}

export const api = axios.create(axiosDefaults)

api.interceptors.request.use(
    async function (config) {
        return config
    },
    function (error) {
        return Promise.reject(error)
    }
)

api.interceptors.response.use(
    function (response) {
        return response
    },
    function (error) {
        if (error instanceof AxiosError) {
            if (error.code === 'ERR_NETWORK') {
                toast('Network Error', {
                    description: 'Failed to connect',
                })
            }

            if (error.response?.status === 500) {
                toast('Internal Server Error', {
                    description: 'An error occurred',
                })
            }

            if (error.response?.status === 429) {
                toast('Too Many Requests', {
                    description: 'Wait a bit and try again',
                })
            }

            if (error.response?.status === 401 && error.config?.url !== '/me') {
                window.location.reload()
            }
        }

        return Promise.reject(error)
    }
)

export async function get<T>(schema: z.ZodType<T>, path: string) {
    return await api
        .get<z.ZodType<T>>(path)
        .then((response) => schema.parse(response.data))
        .catch((err) => {
            console.error(err)

            throw err
        })
}

export async function post<T, D>(schema: z.ZodType<T>, path: string, data: D) {
    return await api
        .post<z.ZodType<T>, AxiosResponse<z.ZodType<T>>, D>(path, data)
        .then((response) => schema.parse(response.data))
        .catch((err) => {
            console.error(err)

            throw err
        })
}

export async function patch<T, D>(schema: z.ZodType<T>, path: string, data?: D) {
    return await api
        .patch<z.ZodType<T>, AxiosResponse<z.ZodType<T>>, D>(path, data || ({} as D))
        .then((response) => schema.parse(response.data))
        .catch((err) => {
            console.error(err)

            throw err
        })
}

export async function del<T>(schema: z.ZodType<T>, path: string) {
    return await api
        .delete<z.ZodType<T>>(path)
        .then((response) => schema.parse(response.data))
        .catch((err) => {
            console.error(err)

            throw err
        })
}

export function is404(query: UseQueryResult<unknown>) {
    return query.isError && axios.isAxiosError(query.error) && query.error.status === 404
}
