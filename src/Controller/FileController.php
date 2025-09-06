<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\File;
use App\Repository\FileRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route('/api/files')]
class FileController extends AbstractController
{
    protected const DOCUMENT_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'application/rtf',
        'application/zip',
        'application/x-rar-compressed',
        'application/json',
        'application/xml',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
    ];

    protected const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/svg+xml',
        'image/tiff',
        'image/tif',
        'image/heic',
        'image/heif',
    ];

    #[Route('/upload/{folderId}', name: 'app_file_upload_to_folder', methods: ['POST'])]
    #[Route('/upload', name: 'app_file_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        EntityManagerInterface $manager,
        ?int $folderId,
        FileRepository $fileRepository,
    ): Response {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        if ($request->get('folderName')) {
            return $this->json(['error' => 'Folders cannot be uploaded'], 400);
        }

        $uploadedFile = new File();
        $uploadedFile->setOriginalName($file->getClientOriginalName());
        $uploadedFile->setSize($file->getSize());
        $uploadedFile->setMimeType($file->getMimeType());
        $uploadedFile->setOwner($this->getUser());
        $uploadedFile->setFile($file);

        if ($folderId) {
            /**
             * @var File $folder
             */
            $folder = $fileRepository->find($folderId);
            if (!$folder || $folder->getOwner() !== $this->getUser() || !$folder->isFolder()) {
                return $this->json(['error' => 'Folder not found'], 404);
            }
            $uploadedFile->setParent($folder);
        }

        $manager->persist($uploadedFile);
        $manager->flush();

        return $this->json(
            $uploadedFile,
            201,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    #[Route('/all', name: 'app_file_get_files_from_user', methods: ['GET'])]
    public function getFilesFromUser(): Response
    {
        /** @var \App\Entity\User|null */
        $user = $this->getUser();
        $files = $user->getFiles();
        $sharedFiles = $user->getSharedFiles();
        $allFiles = new ArrayCollection(
            array_merge($files->toArray(), $sharedFiles->toArray())
        );

        return $this->json(
            $allFiles,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    #[Route('/download/{id}', name: 'app_file_download', methods: ['GET'])]
    public function downloadDocument(
        ?File $fileDownloadable,
    ): Response {
        if (!$fileDownloadable) {
            return $this->json(['error' => 'File not found'], 404);
        }

        if ($fileDownloadable->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'You are not the owner of this file'], 403);
        }

        $file = sprintf('%s/public/%s', $this->getParameter('kernel.project_dir'), $fileDownloadable->getFilepath());

        return $this->file($file, $fileDownloadable->getOriginalName());
    }

    #[Route('/remove/{id}', name: 'app_file_delete', methods: ['DELETE'])]
    public function removeFile(?File $file, EntityManagerInterface $manager): Response
    {
        if (!$file) {
            return $this->json(['error' => 'File not found'], 404);
        }

        if ($file->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'You are not the owner of this file'], 403);
        }

        $manager->remove($file);
        $manager->flush();

        return $this->json(
            ['message' => 'File removed successfully'],
            200
        );
    }

    #[Route('/share/{id}', name: 'app_file_share', methods: ['POST'])]
    public function shareFile(?File $file, Request $request, EntityManagerInterface $manager, UserRepository $userRepository): Response
    {
        if (!$file) {
            return $this->json(['error' => 'File not found'], 404);
        }

        if ($file->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'You are not the owner of this file'], 403);
        }

        $data = $request->toArray();
        $userIds = $data['userIds'] ?? [];

        foreach ($userIds as $userId) {
            $file->shareWith($userRepository->find($userId));
        }

        $manager->persist($file);
        $manager->flush();

        return $this->json(
            ['message' => 'File shared successfully'],
            200
        );
    }

    #[Route('/revoke/{id}', name: 'app_file_revoke', methods: ['POST'])]
    public function revokeFileAccess(?File $file, Request $request, EntityManagerInterface $manager, UserRepository $userRepository): Response
    {
        if (!$file) {
            return $this->json(['error' => 'File not found'], 404);
        }

        if ($file->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'You are not the owner of this file'], 403);
        }

        $data = $request->toArray();
        $userIds = $data['userIds'] ?? [];

        foreach ($userIds as $userId) {
            $file->revokeAccess($userRepository->find($userId));
        }

        $manager->persist($file);
        $manager->flush();

        return $this->json(
            ['message' => 'File access revoked successfully'],
            200
        );
    }

    #[Route('/show/{id}', name: 'app_file_data', methods: ['GET'])]
    public function getFileData(?File $file): Response
    {
        if (!$file) {
            return $this->json(['error' => 'File not found'], 404);
        }

        if ($file->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'You are not the owner of this file'], 403);
        }

        return $this->json(
            $file,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    #[Route('/recent', name: 'app_file_recent', methods: ['GET'])]
    public function getRecentFiles(): Response
    {
        /** @var \App\Entity\User|null */
        $user = $this->getUser();
        $files = $user->getFiles();
        $sharedFiles = $user->getSharedFiles();

        $allFiles = new ArrayCollection(
            array_merge($files->toArray(), $sharedFiles->toArray())
        );

        $sortedFiles = $allFiles->toArray();
        usort($sortedFiles, fn($a, $b) => $b->getUploadedAt() <=> $a->getUploadedAt());

        $recentFiles = array_slice($sortedFiles, 0, 5);

        return $this->json(
            $recentFiles,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    #[Route('/folders', name: 'app_file_folders_index', methods: ['GET'])]
    public function getFoldersFiles(): Response
    {
        /** @var \App\Entity\User|null */
        $user = $this->getUser();
        $files = $user->getFiles();
        $sharedFiles = $user->getSharedFiles();

        $allFiles = new ArrayCollection(
            array_merge($files->toArray(), $sharedFiles->toArray())
        );

        $sortedFiles = $allFiles->toArray();

        $foldersFile = array_filter($sortedFiles, fn($file) => $file->isFolder());
        $foldersFile = array_values($foldersFile);

        return $this->json(
            $foldersFile,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    #[Route('/documents', name: 'app_file_documents_index', methods: ['GET'])]
    public function getDocumentsFiles(): Response
    {
        /** @var \App\Entity\User|null */
        $user = $this->getUser();
        $files = $user->getFiles();
        $sharedFiles = $user->getSharedFiles();

        $allFiles = new ArrayCollection(
            array_merge($files->toArray(), $sharedFiles->toArray())
        );

        $sortedFiles = $allFiles->toArray();

        $documentsFile = array_filter($sortedFiles, fn($file) => in_array($file->getMimeType(), self::DOCUMENT_MIME_TYPES, true));
        $documentsFile = array_values($documentsFile);

        return $this->json(
            $documentsFile,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    #[Route('/images', name: 'app_file_images_index', methods: ['GET'])]
    public function getImagesFiles(): Response
    {
        /** @var \App\Entity\User|null */
        $user = $this->getUser();
        $files = $user->getFiles();
        $sharedFiles = $user->getSharedFiles();

        $allFiles = new ArrayCollection(
            array_merge($files->toArray(), $sharedFiles->toArray())
        );

        $sortedFiles = $allFiles->toArray();

        $imagesFile = array_filter($sortedFiles, fn($file) => in_array($file->getMimeType(), self::IMAGE_MIME_TYPES, true));
        $imagesFile = array_values($imagesFile);

        return $this->json(
            $imagesFile,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    #[Route('/folder/create', name: 'app_file_create_folder', methods: ['POST'])]
    public function createFolder(EntityManagerInterface $manager, Request $request, FileRepository $fileRepository): Response
    {
        $user = $this->getUser();

        $folder = new File();
        $data = json_decode($request->getContent(), true);
        $folder->setFilename($data['folderName'] ?? 'New folder');
        $folder->setOwner($user);
        $folder->setIsFolder(true);
        $folder->setMimeType('folder');

        $data = $request->toArray();
        $parentId = $data['parentId'] ?? null;

        if (!empty($parentId)) {
            $parent = $fileRepository->find($parentId);
            if (!$parent || !$parent->isFolder()) {
                return $this->json(['error' => 'Invalid parent folder'], 400);
            }
            $folder->setParent($parent);
        }

        $manager->persist($folder);
        $manager->flush();

        return $this->json(
            $folder,
            201,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    #[Route('/folder/{id}', name: 'app_folder_get_children', methods: ['GET'])]
    public function getChildren(File $folder, FileRepository $fileRepository): Response
    {
        if ($folder->isFolder() && $folder->getOwner() === $this->getUser()) {
            $children = $folder->getChildren();
        } else {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        return $this->json(
            $children,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    #[Route('/move/{id}', name: 'app_file_move', methods: ['POST'])]
    public function moveFile(File $file, EntityManagerInterface $manager, Request $request): Response
    {
        $data = $request->toArray();
        $newParentId = $data['newParentId'] ?? null;

        $file->setParent($newParentId);

        $manager->persist($file);
        $manager->flush();

        return $this->json(
            $file,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    #[Route('/preview/{id}', name: 'file_preview', methods: ['GET'])]
    public function previewFile(File $file, UploaderHelper $uploaderHelper): Response
    {
        $filePath = $uploaderHelper->asset($file, 'file');

        if (!$filePath) {
            throw new NotFoundHttpException('File not found.');
        }

        $fullPath = $this->getParameter('kernel.project_dir').'/public'.$filePath;

        if (!file_exists($fullPath)) {
            throw new NotFoundHttpException('File not found on disk.');
        }

        $response = new BinaryFileResponse($fullPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);

        return $response;
    }
}
