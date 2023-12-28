// Função reutilizável para buscar artigos da API
async function fetchArticles(url) {
  const response = await fetch(url);

  if (!response.ok) {
      throw new Error(`Erro ao buscar artigos: ${response.statusText}`);
  }

  return response.json();
}

// Atualiza a tabela com dados da API
async function updateArticle(event) {
  event.preventDefault();

  const id = document.querySelector("form[action='/update'] input[name='id']").value;
  const name = document.querySelector("form[action='/update'] input[name='name']").value;
  const article_body = document.querySelector("form[action='/update'] textarea[name='article_body']").value;
  const author = document.querySelector("form[action='/update'] input[name='author']").value;
  const author_avatar = document.querySelector("form[action='/update'] input[name='author_avatar']").value;

  try {
      const response = await fetch(`http://localhost:9504/update/${id}`, {
          method: "POST",
          headers: {
              "Content-Type": "application/json",
          },
          body: JSON.stringify({
              name,
              article_body,
              author,
              author_avatar,
          }),
      });

      if (response.ok) {
          const articles = await response.json();
          updateTable(articles.data);
      } else {
          throw new Error(`Erro ao atualizar artigo: ${response.statusText}`);
      }
  } catch (error) {
      console.error("Erro ao atualizar artigo:", error);
  }
}

async function getArticles() {
  try {
      const articles = await fetchArticles("http://localhost:9504/get");
      updateTable(articles.data);
  } catch (error) {
      console.error("Erro ao buscar artigos:", error);
  }
}

async function editArticle(id) {
  try {
      const article = await fetchArticles(`http://localhost:9504/find/${id}`);
      const form = document.querySelector("form[action='/update']");

      form.querySelector("input[name='id']").value = article.id;
      form.querySelector("input[name='name']").value = article.name;
      form.querySelector("textarea[name='article_body']").value = article.article_body;
      form.querySelector("input[name='author']").value = article.author;
      form.querySelector("input[name='author_avatar']").value = article.author_avatar;
  } catch (error) {
      console.error("Erro ao buscar artigo:", error);
  }
}

async function createArticle(event) {
  event.preventDefault();

  const name = document.querySelector("form[action='/create'] input[name='name']").value;
  const article_body = document.querySelector("form[action='/create'] textarea[name='article_body']").value;
  const author = document.querySelector("form[action='/create'] input[name='author']").value;
  const author_avatar = document.querySelector("form[action='/create'] input[name='author_avatar']").value;

  try {
      const response = await fetch("http://localhost:9504/create", {
          method: "POST",
          headers: {
              "Content-Type": "application/json",
          },
          body: JSON.stringify({
              name,
              article_body,
              author,
              author_avatar,
          }),
      });

      if (response.ok) {
          const articles = await response.json();
          updateTable(articles.data);
      } else {
          throw new Error(`Erro ao criar artigo: ${response.statusText}`);
      }
  } catch (error) {
      console.error("Erro ao criar artigo:", error);
  }
}

document.addEventListener("DOMContentLoaded", getArticles);
