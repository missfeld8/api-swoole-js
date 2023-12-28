const articleList = document.getElementById("article-list");
let articles = [];

const addArticleBtn = document.getElementById("add");

addArticleBtn.addEventListener('click', () => {
    const author_avatar = document.getElementById('add_avatar').value;
    const name = document.getElementById('add_nome').value;
    const author = document.getElementById('add_author').value;
    const article_body = document.getElementById('add_article').value;

    const article = {
        author_avatar,
        name,
        author,
        article_body,
    };

    fetch('http://localhost:9504/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(article)
    }).then(resp => {
        if (resp.ok) {
            getAll();
        }
    });
});

const search = document.getElementById('busca');

search.addEventListener('keyup', (e) => {
    const query = e.target.value;
    if (e.key === 'Enter') {
        const articleDescriptionContainer = document.getElementById('article_body');
        articleDescriptionContainer.classList.add('hidden');
        find(query);
    }
});

const addArticleInfo = (data) => {
    articleList.innerHTML = '';
    data.forEach(article => {
        const articleContainer = document.createElement('div');
        articleContainer.classList.add('container-info-car');
        articleContainer.dataset.id = article.id;
        articleContainer.innerHTML = `
            <p class="img"><img class="author_avatar" src="${article.author_avatar}" alt="avatar"></p>
            <p class="name-people">${article.name}</p>
            <p class="author">${article.author}</p>
            <p class="year-article">${article.year}</p>
            <p class="margin"></p>`;
        articleList.appendChild(articleContainer);

        articleContainer.addEventListener('click', () => {
            const articleBodyContainer = document.getElementById('article_body');
            articleBodyContainer.classList.remove('hidden');
            const id = articleContainer.dataset.id;
            const selectedArticle = articles.find(article => article.id == id);

            const descAuthor = document.getElementById('author');
            const descCreatedAt = document.getElementById('created_at');
            const descDescricao = document.getElementById('descricao');

            descAuthor.innerText = selectedArticle.author;
            descCreatedAt.innerText = selectedArticle.created_at;
            descDescricao.innerText = selectedArticle.article_body;
        });
    });
};

const getAll = async () => {
    const resp = await fetch('http://localhost:9504/get');
    const data = await resp.json();
    articles = data;
    addArticleInfo(data);
};

const find = async (query) => {
    const resp = await fetch(`http://localhost:9504/find?q=${query}`);
    if (!resp.ok) {
        articles = [];
    } else {
        const data = await resp.json();
        articles = data;
    }
    addArticleInfo(articles);
};

document.addEventListener('DOMContentLoaded', () => {
    getAll();
});
