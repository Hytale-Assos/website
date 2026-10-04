export type HytaleServer = {
    id: string;
    module_id: string;
    name: string;
    url: string;
    created_at: string | null;
    updated_at: string | null;
};

export type WhitelistEntry = {
    id: string;
    hytale_server_id: string;
    hytale_id: string;
    created_at: string | null;
    updated_at: string | null;
};

export type PlayerSession = {
    id: string;
    hytale_server_id: string;
    hytale_id: string;
    joined_at: string | null;
    ended_at: string | null;
    created_at: string | null;
    updated_at: string | null;
};
