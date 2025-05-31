
import React, { useState } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useMutation, useApi } from '@/hooks/useApi';
import { pageService } from '@/services/pageService';
import { userService } from '@/services/userService';
import { toast } from '@/components/ui/use-toast';

interface CreatePageDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onPageCreated: () => void;
}

const CreatePageDialog = ({ open, onOpenChange, onPageCreated }: CreatePageDialogProps) => {
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [selectedUserId, setSelectedUserId] = useState<string>('');

  // Загружаем список пользователей для выбора
  const { data: usersData } = useApi(() => userService.getUsers({ limit: 100 }), []);
  const users = usersData?.users || [];

  const { mutate: createPage, loading } = useMutation(pageService.createPage.bind(pageService));

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!title.trim() || !selectedUserId) {
      toast({
        title: "Ошибка",
        description: "Заголовок и пользователь обязательны для заполнения",
        variant: "destructive",
      });
      return;
    }

    const result = await createPage({
      user_id: parseInt(selectedUserId),
      title: title.trim(),
      content: content.trim()
    });

    if (result) {
      toast({
        title: "Успех",
        description: "Страница успешно создана",
      });
      setTitle('');
      setContent('');
      setSelectedUserId('');
      onOpenChange(false);
      onPageCreated();
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle>Создать страницу</DialogTitle>
          <DialogDescription>
            Добавьте новую текстовую страницу для пользователя
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className="grid gap-4 py-4">
            <div className="grid gap-2">
              <Label htmlFor="user">Пользователь</Label>
              <Select value={selectedUserId} onValueChange={setSelectedUserId}>
                <SelectTrigger>
                  <SelectValue placeholder="Выберите пользователя" />
                </SelectTrigger>
                <SelectContent>
                  {users.map((user) => (
                    <SelectItem key={user.id} value={user.id.toString()}>
                      {user.email}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid gap-2">
              <Label htmlFor="title">Заголовок</Label>
              <Input
                id="title"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="Введите заголовок страницы"
                required
              />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="content">Содержимое</Label>
              <Textarea
                id="content"
                value={content}
                onChange={(e) => setContent(e.target.value)}
                placeholder="Введите содержимое страницы..."
                rows={6}
              />
            </div>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              Отмена
            </Button>
            <Button type="submit" disabled={loading}>
              {loading ? 'Создание...' : 'Создать'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default CreatePageDialog;
