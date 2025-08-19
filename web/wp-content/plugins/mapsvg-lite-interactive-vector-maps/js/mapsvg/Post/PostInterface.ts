interface PostInterface {
    id: number;
    post_content: string;
    post_title: string;
    url: string;
    acf?: { [key: string]: any };
}

export { PostInterface };
