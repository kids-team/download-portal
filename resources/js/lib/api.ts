import axios from "axios";
import { useQuery, type UseQueryOptions } from "@tanstack/react-query";

export const api = axios.create({
    headers: { Accept: "application/json" },
    withCredentials: true,
    withXSRFToken: true,
});

export function useApiQuery<TData = any>(
    path: string,
    options: Omit<UseQueryOptions<TData, Error>, "queryKey" | "queryFn"> = {},
) {
    return useQuery<TData, Error>({
        queryKey: [path],
        queryFn: async () => (await api.get<{ data: TData }>(path)).data.data,
        ...options,
    });
}
