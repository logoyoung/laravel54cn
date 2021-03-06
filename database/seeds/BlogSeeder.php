<?php

use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		//清空所有数据
		DB::table('blogs')->delete();
		DB::table('author')->delete();
		DB::table('comments')->delete();
		DB::table('subjects')->delete();
		DB::table('blogs_subjects')->delete();
		//填充blog表，创建三篇文章
		$php   = \App\Blog::create(
			['title' => 'PHP的未来']
		);
		$java  = \App\Blog::create(
			['title' => 'Java的未来']
		);
		$html5 = \App\Blog::create(
			['title' => 'HTML5的未来']
		);
		//命令行文字提示
		$this->command->info('The blogs are seeded!');
		//填充author表
		\App\Author::create([
			'name'    => 'PHP的作者',
			'blog_id' => $php->id
		]);
		\App\Author::create([
			'name'    => 'Java的作者',
			'blog_id' => $java->id
		]);
		\App\Author::create([
			'name'    => 'HTML5的作者',
			'blog_id' => $html5->id
		]);
		//填充comments表
		\App\Comment::create([
			'content' => 'PHP多用于服务器程序编写',
			'words'   => 13,
			'blog_id' => $php->id
		]);
		\App\Comment::create([
			'content' => 'PHP是无类型编程语言',
			'words'   => 11,
			'blog_id' => $php->id
		]);
		//填充subjects表
		$language = \App\Subject::create([
			'name' => '计算机语言'
		]);
		$program  = \App\Subject::create([
			'name' => '编程语言'
		]);
		//关联blogs与subjects
		$php->subjects()->attach($language->id);
		$php->subjects()->attach($program->id);
		$java->subjects()->attach($language->id);
		$java->subjects()->attach($program->id);
		$html5->subjects()->attach($language->id);

	}
}
