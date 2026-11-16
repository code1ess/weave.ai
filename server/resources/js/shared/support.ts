export type SupportThreadProps = {
  staffView?: boolean;
  ticket: {
    id: string;
    subject: string;
    body: string;
    status: string;
    task_id: string | null;
    created_at: string;
  };
  messages: {
    data: {
      id: number;
      body: string;
      admin_user_id: number | null;
      created_at: string;
    }[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
};
