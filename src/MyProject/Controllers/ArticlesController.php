<?php

namespace MyProject\Controllers;

use MyProject\Exceptions\InvalidArgumentException;
use MyProject\Exceptions\NotFoundException;
use MyProject\Exceptions\UnauthorizedException;
use MyProject\Models\Articles\Article;
use MyProject\Models\Comments\Comment;
use MyProject\Models\Users\User;

class ArticlesController extends AbstractController
{
    /**
     * Отображает страницу отдельно взятой статьи
     */
    public function view(int $articleId): void
    {
        # Если пользователь не авторизован, он будет перенаправлен на страницу с предложением войти в аккаунт
        if ($this->user === null) {
            throw new UnauthorizedException();
        }

        // Объект класса Article, свойства которого заполнены данными из БД
        $article = Article::getById($articleId);

        // Массив объектов класса Comment, относящихся к данной статье
        $comments = Comment::findAllByColumn('article_id', $articleId, true);

        if ($article === null)
        {
            throw new NotFoundException();
        }

        $this->view->renderHtml('articles/view.php', [
            'article' => $article,
            'comments' => $comments,
        ]);
    }

    public function add(): void
    {
        if ($this->user === null) {
            throw new UnauthorizedException();
        }

        if (!empty($_POST))
        {
            try {
                $article = Article::createFromArray($_POST, $this->user);
            } catch (InvalidArgumentException $e) {
                $this->view->renderHtml('articles/add.php', ['error' => $e->getMessage()]);
                return;
            }

            header('Location:' . $this->url . 'articles/' . $article->getId(), true, 302);
            exit();
        }

        $this->view->renderHtml('articles/add.php');
    }

    public function edit(int $articleId): void
    {
        $article = Article::getById($articleId);

        if ($article === null)
        {
            throw new NotFoundException();
        }

        if ($this->user === null)
        {
            throw new UnauthorizedException();
        }

        if (!empty($_POST))
        {
            try {
                $article->updateFromArray($_POST);
            } catch (InvalidArgumentException $e) {
                $this->view->renderHtml('articles/edit.php',
                    [
                        'error' => $e->getMessage(),
                        'article' => $article,
                    ]);
                return;
            }

            header('Location:' . $this->url . 'articles/' . $article->getId(), true, 302);
            exit();
        }

        $this->view->renderHtml('articles/edit.php', ['article' => $article]);
    }

    public function remove(int $articleId): void
    {
        $article = Article::getById($articleId);

        if ($article === null)
        {
            throw new NotFoundException();
        }

        $article->delete();

        \header('Location: '. $this->url);
    }
}